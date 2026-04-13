<?php

namespace App\Support\League;

class TeamData
{
    public function __construct(
        public string $teamName,
        public int $strength,
        public int $average = 0,
        public int $totalPoint = 0,
        public int $winningMatchCount = 0,
        public int $withdrawnMatchCount = 0,
        public int $lostMatchCount = 0,
        public int $totalPlayedMatch = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            teamName: (string) ($data['teamName'] ?? ''),
            strength: (int) ($data['strength'] ?? LeagueConstants::DEFAULT_TEAM_STRENGTH),
            average: (int) ($data['average'] ?? 0),
            totalPoint: (int) ($data['totalPoint'] ?? 0),
            winningMatchCount: (int) ($data['winningMatchCount'] ?? 0),
            withdrawnMatchCount: (int) ($data['withdrawnMatchCount'] ?? 0),
            lostMatchCount: (int) ($data['lostMatchCount'] ?? 0),
            totalPlayedMatch: (int) ($data['totalPlayedMatch'] ?? 0),
        );
    }

    public function addAverage(int $goalDiff): void
    {
        $this->average += $goalDiff;

        if ($goalDiff > 0) {
            $this->winningMatchCount++;
            $this->totalPoint += 3;
        } elseif ($goalDiff === 0) {
            $this->withdrawnMatchCount++;
            $this->totalPoint += 1;
        } else {
            $this->lostMatchCount++;
        }

        $this->totalPlayedMatch++;
    }

    public function clone(): self
    {
        return new self(
            teamName: $this->teamName,
            strength: $this->strength,
            average: $this->average,
            totalPoint: $this->totalPoint,
            winningMatchCount: $this->winningMatchCount,
            withdrawnMatchCount: $this->withdrawnMatchCount,
            lostMatchCount: $this->lostMatchCount,
            totalPlayedMatch: $this->totalPlayedMatch,
        );
    }

    public function add(self $other): self
    {
        $team = $this->clone();

        if (strcasecmp($team->teamName, $other->teamName) !== 0) {
            return $team;
        }

        $team->average += $other->average;
        $team->totalPoint += $other->totalPoint;
        $team->winningMatchCount += $other->winningMatchCount;
        $team->withdrawnMatchCount += $other->withdrawnMatchCount;
        $team->lostMatchCount += $other->lostMatchCount;
        $team->totalPlayedMatch += $other->totalPlayedMatch;

        return $team;
    }

    public function toArray(): array
    {
        return [
            'teamName' => $this->teamName,
            'strength' => $this->strength,
            'average' => $this->average,
            'totalPoint' => $this->totalPoint,
            'winningMatchCount' => $this->winningMatchCount,
            'withdrawnMatchCount' => $this->withdrawnMatchCount,
            'lostMatchCount' => $this->lostMatchCount,
            'totalPlayedMatch' => $this->totalPlayedMatch,
        ];
    }
}
