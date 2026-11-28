<?php

namespace App\Modules\Campus\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Reserve, issue and return. Any charge goes to the student's one fee ledger. */
class LibraryService
{
    public function __construct(private FinanceService $finance, private Notifier $notifier)
    {
    }

    public function books(int $schoolId, ?string $search = null, int $limit = 100)
    {
        $out = DB::table('library_loans')->whereIn('status', ['issued', 'reserved'])->groupBy('book_id')->selectRaw('book_id, count(*) as c')->pluck('c', 'book_id');

        return DB::table('library_books')->where('school_id', $schoolId)
            ->when($search, fn ($q, $s) => $q->where(fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('author', 'like', "%{$s}%")->orWhere('isbn', $s)->orWhere('category', 'like', "%{$s}%")))
            ->orderBy('title')->limit($limit)->get()
            ->map(function ($b) use ($out) {
                $b->available = max(0, $b->copies - (int) ($out[$b->id] ?? 0));

                return $b;
            });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['book' => $message]);
    }

    /** Locks the book so two people cannot take the last copy. */
    private function take(int $bookId, int $schoolId, int $studentId): object
    {
        $book = DB::table('library_books')->where('id', $bookId)->where('school_id', $schoolId)->lockForUpdate()->first();
        $book ?: $this->fail('Book not found.');
        $out = DB::table('library_loans')->where('book_id', $bookId)->whereIn('status', ['issued', 'reserved'])->where('student_id', '!=', $studentId)->count();
        if ($out >= $book->copies) {
            $this->fail('No copy is available right now.');
        }
        if (DB::table('library_loans')->where('book_id', $bookId)->where('student_id', $studentId)->where('status', 'issued')->exists()) {
            $this->fail('This student already has this book.');
        }

        return $book;
    }

    private function assertWithinLimit(int $studentId, ?int $exceptBook = null): void
    {
        $holding = DB::table('library_loans')->where('student_id', $studentId)->whereIn('status', ['issued', 'reserved'])
            ->when($exceptBook, fn ($q) => $q->where('book_id', '!=', $exceptBook))->count();
        if ($holding >= config('campus.library_limit', 3)) {
            $this->fail('The borrowing limit has been reached.');
        }
    }

    public function reserve(int $bookId, int $schoolId, int $studentId): void
    {
        DB::transaction(function () use ($bookId, $schoolId, $studentId) {
            if (DB::table('library_loans')->where('book_id', $bookId)->where('student_id', $studentId)->where('status', 'reserved')->exists()) {
                $this->fail('This book is already reserved for you.');
            }
            $this->take($bookId, $schoolId, $studentId);
            $this->assertWithinLimit($studentId);
            DB::table('library_loans')->insert(['book_id' => $bookId, 'student_id' => $studentId, 'school_id' => $schoolId, 'status' => 'reserved', 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    public function issue(int $bookId, int $schoolId, int $studentId, int $handledBy, int $days = 14): void
    {
        DB::transaction(function () use ($bookId, $schoolId, $studentId, $handledBy, $days) {
            $book = $this->take($bookId, $schoolId, $studentId);
            $this->assertWithinLimit($studentId, $bookId);
            if (DB::table('library_loans')->where('student_id', $studentId)->where('status', 'issued')->where('due_on', '<', now()->toDateString())->exists()) {
                $this->fail('This student has an overdue book to return first.');
            }
            $values = ['status' => 'issued', 'issued_on' => now()->toDateString(), 'due_on' => now()->addDays($days)->toDateString(), 'handled_by' => $handledBy, 'updated_at' => now()];
            $reserved = DB::table('library_loans')->where('book_id', $bookId)->where('student_id', $studentId)->where('status', 'reserved')->value('id');
            $reserved
                ? DB::table('library_loans')->where('id', $reserved)->update($values)
                : DB::table('library_loans')->insert($values + ['book_id' => $bookId, 'student_id' => $studentId, 'school_id' => $schoolId, 'created_at' => now()]);

            $this->notifier->toFamilies([$studentId], 'library', 'Book issued', "{$book->title} is due " . now()->addDays($days)->format('j M') . '.', ['screen' => 'library']);
        });
    }

    /** @param string $outcome returned, lost or cancelled */
    public function close(int $loanId, int $schoolId, string $outcome, int $handledBy, int $charge = 0): int
    {
        return DB::transaction(function () use ($loanId, $schoolId, $outcome, $handledBy, $charge) {
            $loan = DB::table('library_loans')->where('id', $loanId)->where('school_id', $schoolId)->lockForUpdate()->first();
            abort_unless($loan, 404);
            $allowed = $loan->status === 'issued' ? ['returned', 'lost'] : ($loan->status === 'reserved' ? ['cancelled'] : []);
            if (!in_array($outcome, $allowed, true)) {
                $this->fail('That loan is already closed.');
            }

            $late = $loan->due_on && $outcome === 'returned' ? max(0, (int) \Carbon\Carbon::parse($loan->due_on)->diffInDays(now()->startOfDay(), false)) : 0;
            $fine = $late * (int) config('campus.library_fine_per_day', 5) + ($outcome === 'cancelled' ? 0 : max(0, $charge));

            DB::table('library_loans')->where('id', $loanId)->update(['status' => $outcome, 'returned_on' => now()->toDateString(), 'fine' => $fine, 'handled_by' => $handledBy, 'updated_at' => now()]);
            if ($fine > 0) {
                $title = DB::table('library_books')->where('id', $loan->book_id)->value('title');
                $this->finance->invoice((int) $loan->student_id, "Library charge: {$title}", $fine);
            }
            if ($outcome === 'lost') {
                DB::table('library_books')->where('id', $loan->book_id)->where('copies', '>', 0)->decrement('copies');
            }

            return $fine;
        });
    }

    public function loans(int $studentId): array
    {
        return DB::table('library_loans')->join('library_books', 'library_books.id', '=', 'library_loans.book_id')
            ->where('student_id', $studentId)->orderByRaw("status in ('issued','reserved') desc")->orderByDesc('library_loans.id')->limit(40)
            ->get(['library_loans.id', 'library_books.title', 'library_books.author', 'status', 'issued_on', 'due_on', 'returned_on', 'fine'])
            ->map(fn ($l) => (array) $l + ['overdue' => $l->status === 'issued' && $l->due_on < now()->toDateString()])->all();
    }
}
