<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PresenceService
{
    protected const PRESENCE_TTL = 30; // seconds

    public static function track(string $type, int $id, int $userId): void
    {
        $key = self::getKey($type, $id);
        $presence = Cache::get($key, []);

        $presence[$userId] = [
            'user_id' => $userId,
            'last_seen' => now()->timestamp,
        ];

        // Clean up stale entries
        $presence = array_filter($presence, function ($entry) {
            return (now()->timestamp - $entry['last_seen']) < self::PRESENCE_TTL;
        });

        Cache::put($key, $presence, self::PRESENCE_TTL + 10);
    }

    public static function leave(string $type, int $id, int $userId): void
    {
        $key = self::getKey($type, $id);
        $presence = Cache::get($key, []);

        unset($presence[$userId]);

        Cache::put($key, $presence, self::PRESENCE_TTL + 10);
    }

    public static function getViewers(string $type, int $id): array
    {
        $key = self::getKey($type, $id);
        $presence = Cache::get($key, []);

        // Clean up stale entries and return
        return array_filter($presence, function ($entry) {
            return (now()->timestamp - $entry['last_seen']) < self::PRESENCE_TTL;
        });
    }

    public static function getViewerCount(string $type, int $id): int
    {
        return count(self::getViewers($type, $id));
    }

    public static function getViewerIds(string $type, int $id): array
    {
        return array_keys(self::getViewers($type, $id));
    }

    protected static function getKey(string $type, int $id): string
    {
        return "presence:{$type}:{$id}";
    }
}
