<?php

namespace App\Support\League;

use Illuminate\Support\Facades\File;

class LeagueStateRepository
{
    public function getState(): array
    {
        $path = $this->path();

        if (! File::exists($path)) {
            $this->saveState($this->defaultState());
        }

        $state = json_decode((string) File::get($path), true);

        return is_array($state) ? $state : $this->defaultState();
    }

    public function saveState(array $state): void
    {
        $path = $this->path();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function resetState(): void
    {
        $this->saveState($this->defaultState());
    }

    private function path(): string
    {
        return config('league.state_path');
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
