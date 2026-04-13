<?php

namespace App\Support\League;

class MatchResultData
{
    public function __construct(
        public TeamData $homeTeam,
        public TeamData $otherTeam,
        public int $homeGoalCount = 0,
        public int $otherTeamGoalCount = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            homeTeam: TeamData::fromArray($data['homeTeam'] ?? []),
            otherTeam: TeamData::fromArray($data['otherTeam'] ?? []),
            homeGoalCount: (int) ($data['homeGoalCount'] ?? 0),
            otherTeamGoalCount: (int) ($data['otherTeamGoalCount'] ?? 0),
        );
    }

    public function clone(): self
    {
        return new self(
            homeTeam: $this->homeTeam->clone(),
            otherTeam: $this->otherTeam->clone(),
            homeGoalCount: $this->homeGoalCount,
            otherTeamGoalCount: $this->otherTeamGoalCount,
        );
    }

    public function toArray(): array
    {
        return [
            'homeTeam' => $this->homeTeam->toArray(),
            'otherTeam' => $this->otherTeam->toArray(),
            'homeGoalCount' => $this->homeGoalCount,
            'otherTeamGoalCount' => $this->otherTeamGoalCount,
        ];
    }
}
