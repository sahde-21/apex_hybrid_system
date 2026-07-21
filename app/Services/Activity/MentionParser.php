<?php

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\ActivityMention;
use App\Models\User;
use Illuminate\Support\Collection;

class MentionParser
{
    /**
     * Resolve @Name mentions from body against active users (longest name wins).
     *
     * @return list<User>
     */
    public function resolveAndAttach(Activity $activity, string $body, User $actor): array
    {
        $users = $this->matchUsers($body);

        foreach ($users as $user) {
            ActivityMention::query()->firstOrCreate([
                'activity_id' => $activity->id,
                'user_id' => $user->id,
            ]);
        }

        return $users->all();
    }

    /**
     * @return Collection<int, User>
     */
    public function matchUsers(string $body): Collection
    {
        $candidates = User::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderByRaw('LENGTH(name) DESC')
            ->get();

        $matched = collect();
        $haystack = $body;

        foreach ($candidates as $user) {
            $pattern = '/@'.preg_quote($user->name, '/').'(?=$|[^A-Za-z0-9_])/u';
            if (preg_match($pattern, $haystack) === 1) {
                $matched->push($user);
                // Prevent shorter overlapping names from double-matching the same token.
                $haystack = preg_replace($pattern, ' ', $haystack, 1) ?? $haystack;
            }
        }

        return $matched->unique('id')->values();
    }

    /**
     * @return list<string>
     */
    public function extractMentionNames(string $body): array
    {
        return $this->matchUsers($body)->pluck('name')->all();
    }

    /**
     * @return Collection<int, User>
     */
    public function suggest(User $actor, string $query, int $limit = 8): Collection
    {
        $query = trim($query);
        if (strlen($query) < 1) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where('id', '!=', $actor->id)
            ->where('name', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'avatar_path']);
    }
}
