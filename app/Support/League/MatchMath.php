<?php

namespace App\Support\League;

class MatchMath
{
    public static function calculateMatchResultOfTeams(MatchResultData $matchResult): void
    {
        $goalDiff = $matchResult->homeGoalCount - $matchResult->otherTeamGoalCount;

        $homeClone = $matchResult->homeTeam->clone();
        $otherClone = $matchResult->otherTeam->clone();

        $homeClone->addAverage($goalDiff);
        $otherClone->addAverage(-$goalDiff);

        $matchResult->homeTeam = $homeClone;
        $matchResult->otherTeam = $otherClone;
    }

    public static function poisson(float $lambda): int
    {
        $limit = exp(-$lambda);
        $k = 0;
        $p = 1.0;

        do {
            $k++;
            $p *= lcg_value();
        } while ($p > $limit);

        return $k - 1;
    }
}
