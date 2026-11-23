<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * The "MCC Media" feed: announcements (images) and videos in one list, newest first.
 *
 * Sources today: config('landing.media') + introduction videos from config('faculty.members').
 * When the backend is ready, replace the body of items() with a database query that returns
 * the same array shape — the views won't need to change.
 */
class MediaFeed
{
    /** @return Collection<int, array> */
    public static function all(): Collection
    {
        return self::items()
            ->map(fn (array $item) => self::normalise($item))
            ->sortBy([
                // Pinned positions first ('first' items lead, 'last' items trail), then newest first.
                fn (array $a, array $b) => self::rank($a) <=> self::rank($b),
                fn (array $a, array $b) => ($b['date'] ?? '') <=> ($a['date'] ?? ''),
            ])
            ->values();
    }

    /** position: 'first' | 'last' | null (null = ordered by date). */
    private static function rank(array $item): int
    {
        return match ($item['position'] ?? null) {
            'first' => 0,
            'last' => 2,
            default => 1,
        };
    }

    /** Images only (official posters / announcements). */
    public static function images(): Collection
    {
        return self::all()->where('type', 'image')->values();
    }

    private static function items(): Collection
    {
        $people = collect(config('faculty.members', []))
            ->filter(fn (array $m) => !empty($m['video']))
            ->map(fn (array $m) => [
                'type' => 'video',
                'date' => $m['date'] ?? null,
                'src' => $m['video'],
                'poster' => $m['poster'] ?? $m['photo'] ?? null,
                'tag' => $m['tag'] ?? 'Meet our faculty',
                'title' => $m['name'],
                'subtitle' => $m['role'],
                'caption' => $m['bio'] ?? null,
                'highlights' => collect($m['education'] ?? [])
                    ->flatMap(fn (array $e) => [$e['degree'], $e['institution']])
                    ->push(!empty($m['experience']) ? 'Former ' . $m['experience'] : null)
                    ->filter()->unique()->values()->all(),
                'profile' => route(($m['group'] ?? 'teacher') === 'staff' ? 'frontend.staff_profile' : 'frontend.teacher_profile', $m['slug']),
            ]);

        return $people->concat(config('landing.media', []));
    }

    private static function normalise(array $item): array
    {
        $item['type'] = ($item['type'] ?? 'image') === 'video' ? 'video' : 'image';
        if ($item['type'] === 'video') {
            $item['ready'] = !empty($item['src']) && file_exists(public_path($item['src']));
            $item['thumb'] = $item['poster'] ?? null;
        } else {
            $item['thumb'] = $item['image'] ?? null;
        }

        return $item;
    }
}
