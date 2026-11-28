<?php

namespace App\Modules\Campus\Services;

use App\Modules\Cms\Models\CmsNotice;
use Illuminate\Support\Facades\DB;

/** Office noticeboard entries and published website notices, newest first. */
class NoticeService
{
    public function latest(int $schoolId, int $limit = 20): array
    {
        $board = DB::table('noticeboard')->where('school_id', $schoolId)->orderByDesc('id')->limit($limit)->get()
            ->map(fn ($n) => [
                'id' => 'board-' . $n->id,
                'title' => $n->notice_title,
                'body' => $this->plain($n->notice),
                'date' => $this->date($n->start_date),
            ]);

        $website = CmsNotice::live()->limit($limit)->get()->map(fn (CmsNotice $n) => [
            'id' => 'web-' . $n->id,
            'title' => $n->title,
            'body' => $this->plain($n->body),
            'date' => $this->date($n->publish_on ?: $n->created_at),
        ]);

        return $board->concat($website)->sortByDesc('date')->take($limit)->values()->all();
    }

    private function plain(?string $html): string
    {
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", (string) $html));

        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5));
    }

    private function date($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $time = is_numeric($value) ? (int) $value : strtotime((string) $value);

        return $time ? date('Y-m-d', $time) : null;
    }
}
