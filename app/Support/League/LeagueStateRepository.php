<?php

namespace App\Support\League;

use Illuminate\Support\Facades\Cache;

class LeagueStateRepository
{
    private const CACHE_KEY = 'league_state';

    public function getState(): array
    {
        return Cache::get(self::CACHE_KEY, $this->defaultState());
    }

    public function saveState(array $state): void
    {
        Cache::forever(self::CACHE_KEY, $state);
    }

    public function resetState(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function defaultState(): array
    {
        return [
            'teams' => [],
            'matchResults' => [],
            'currentWeek' => 0,
        ];
    }
}
