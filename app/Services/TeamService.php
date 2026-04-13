<?php

namespace App\Services;

use App\Support\League\LeagueStateRepository;
use App\Support\League\TeamData;

class TeamService
{
    public function __construct(private LeagueStateRepository $stateRepository) {}

    public function getTeams(): array
    {
        $state = $this->stateRepository->getState();

        return array_map(fn (array $team) => TeamData::fromArray($team), $state['teams'] ?? []);
    }

    public function addTeam(array $payload): bool
    {
        $team = TeamData::fromArray($payload);

        if ($team->teamName === '') {
            return false;
        }

        $state = $this->stateRepository->getState();

        foreach ($state['teams'] as $existingTeam) {
            if (strcasecmp($existingTeam['teamName'], $team->teamName) === 0) {
                return false;
            }
        }

        $state['teams'][] = $team->toArray();
        $state['matchResults'] = [];
        $state['currentWeek'] = 0;
        $this->stateRepository->saveState($state);

        return true;
    }

    public function updateTeam(string $oldTeamName, string $newTeamName, int $strength): bool
    {
        $state = $this->stateRepository->getState();
        $targetIndex = null;

        foreach ($state['teams'] as $index => $team) {
            if (strcasecmp($team['teamName'], $oldTeamName) === 0) {
                $targetIndex = $index;
                break;
            }
        }

        if ($targetIndex === null) {
            return false;
        }

        foreach ($state['teams'] as $index => $team) {
            if ($index !== $targetIndex && strcasecmp($team['teamName'], $newTeamName) === 0) {
                return false;
            }
        }

        $state['teams'][$targetIndex]['teamName'] = $newTeamName;
        $state['teams'][$targetIndex]['strength'] = $strength;
        $state['matchResults'] = [];
        $state['currentWeek'] = 0;
        $this->stateRepository->saveState($state);

        return true;
    }

    public function deleteTeam(string $teamName): bool
    {
        $state = $this->stateRepository->getState();
        $originalCount = count($state['teams']);

        $state['teams'] = array_values(array_filter(
            $state['teams'],
            fn (array $team) => strcasecmp($team['teamName'], $teamName) !== 0
        ));

        if ($originalCount === count($state['teams'])) {
            return false;
        }

        $state['matchResults'] = [];
        $state['currentWeek'] = 0;
        $this->stateRepository->saveState($state);

        return true;
    }

    public function deleteAllTeams(): void
    {
        $this->stateRepository->resetState();
    }

    public function loadDefaultTeams(): int
    {
        $defaultTeams = [
            new TeamData('Liverpool', 25),
            new TeamData('Manchester City', 25),
            new TeamData('Arsenal', 25),
            new TeamData('Chelsea', 25),
        ];

        $state = $this->stateRepository->getState();
        $addedCount = 0;

        foreach ($defaultTeams as $defaultTeam) {
            $exists = collect($state['teams'])->contains(
                fn (array $team) => strcasecmp($team['teamName'], $defaultTeam->teamName) === 0
            );

            if (! $exists) {
                $state['teams'][] = $defaultTeam->toArray();
                $addedCount++;
            }
        }

        if ($addedCount > 0) {
            $state['matchResults'] = [];
            $state['currentWeek'] = 0;
            $this->stateRepository->saveState($state);
        }

        return $addedCount;
    }

    public function getTotalWeek(): int
    {
        $totalTeamSize = count($this->getTeams());

        if ($totalTeamSize < 2) {
            return 0;
        }

        return ($totalTeamSize - 1) * 2;
    }
}
