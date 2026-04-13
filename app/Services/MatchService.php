<?php

namespace App\Services;

use App\Support\League\LeagueConstants;
use App\Support\League\LeagueStateRepository;
use App\Support\League\MatchMath;
use App\Support\League\MatchResultData;
use App\Support\League\TeamData;

class MatchService
{
    public function __construct(
        private LeagueStateRepository $stateRepository,
        private TeamService $teamService,
    ) {}

    public function generateLeagueMatches(): array
    {
        $state = $this->stateRepository->getState();
        $teams = $this->teamService->getTeams();

        shuffle($teams);

        $totalTeamSize = count($teams);
        $state['currentWeek'] = 0;
        $state['matchResults'] = [];

        if ($totalTeamSize < 4 || $totalTeamSize % 2 !== 0) {
            $this->stateRepository->saveState($state);

            return [];
        }

        $halfWeek = $totalTeamSize - 1;

        for ($weekCounter = 0; $weekCounter < $halfWeek; $weekCounter++) {
            $firstHalf = [];
            $secondHalf = [];

            for ($i = 0; $i < $totalTeamSize / 2; $i++) {
                $homeIndex = ($weekCounter + $i) % $halfWeek;
                $otherIndex = ($halfWeek - $i + $weekCounter) % $halfWeek;

                if ($i === 0) {
                    $otherIndex = $halfWeek;
                }

                $home = $teams[$homeIndex]->clone();
                $other = $teams[$otherIndex]->clone();

                $firstHalf[] = (new MatchResultData($home, $other))->toArray();
                $secondHalf[] = (new MatchResultData($other->clone(), $home->clone()))->toArray();
            }

            $state['matchResults'][(string) $weekCounter] = $firstHalf;
            $state['matchResults'][(string) ($weekCounter + $halfWeek)] = $secondHalf;
        }

        $this->stateRepository->saveState($state);

        return $state['matchResults'];
    }

    public function getMatchResults(): array
    {
        return $this->stateRepository->getState()['matchResults'] ?? [];
    }

    public function getCurrentWeek(): int
    {
        return (int) ($this->stateRepository->getState()['currentWeek'] ?? 0);
    }

    public function playCurrentWeek(): ?array
    {
        $state = $this->stateRepository->getState();
        $currentWeek = (int) ($state['currentWeek'] ?? 0);
        $matchResults = $state['matchResults'] ?? [];

        if ($currentWeek >= $this->teamService->getTotalWeek()) {
            return null;
        }

        $weekMatches = $matchResults[(string) $currentWeek] ?? null;

        if (! is_array($weekMatches)) {
            return null;
        }

        foreach ($weekMatches as $index => $match) {
            $matchResult = MatchResultData::fromArray($match);
            [$homeGoals, $awayGoals] = self::simulateMatchScore(
                $matchResult->homeTeam->strength,
                $matchResult->otherTeam->strength,
            );

            $matchResult->homeGoalCount = $homeGoals;
            $matchResult->otherTeamGoalCount = $awayGoals;
            MatchMath::calculateMatchResultOfTeams($matchResult);
            $weekMatches[$index] = $matchResult->toArray();
        }

        $state['matchResults'][(string) $currentWeek] = $weekMatches;
        $state['currentWeek'] = $currentWeek + 1;
        $this->stateRepository->saveState($state);

        return $weekMatches;
    }

    public function getMatchesUntilWeek(int $week): array
    {
        return $this->getMatchesUntilWeekFromResults($this->getMatchResults(), $week);
    }

    private function getMatchesUntilWeekFromResults(array $matchResults, int $week): array
    {
        $lists = [];

        for ($i = 0; $i < $week; $i++) {
            $matches = $matchResults[(string) $i] ?? [];

            foreach ($matches as $match) {
                $matchResult = MatchResultData::fromArray($match);
                $this->addTeamToList($lists, $matchResult->homeTeam);
                $this->addTeamToList($lists, $matchResult->otherTeam);
            }
        }

        return $lists;
    }

    public function playAllWeeks(): array
    {
        while ($this->getCurrentWeek() < $this->teamService->getTotalWeek()) {
            $this->playCurrentWeek();
        }

        return $this->getStandings();
    }

    public function updateMatchResult(int $week, string $homeTeam, string $otherTeam, int $homeGoals, int $otherGoals): void
    {
        $state = $this->stateRepository->getState();
        $weekMatches = $state['matchResults'][(string) $week] ?? null;

        if (! is_array($weekMatches)) {
            return;
        }

        foreach ($weekMatches as $index => $match) {
            $matchResult = MatchResultData::fromArray($match);

            if (
                strcasecmp($matchResult->homeTeam->teamName, $homeTeam) === 0 &&
                strcasecmp($matchResult->otherTeam->teamName, $otherTeam) === 0
            ) {
                $baseHome = $this->findBaseTeam($homeTeam);
                $baseOther = $this->findBaseTeam($otherTeam);

                if ($baseHome === null || $baseOther === null) {
                    return;
                }

                $updatedResult = new MatchResultData($baseHome, $baseOther, $homeGoals, $otherGoals);
                MatchMath::calculateMatchResultOfTeams($updatedResult);
                $weekMatches[$index] = $updatedResult->toArray();
                $state['matchResults'][(string) $week] = $weekMatches;
                $this->stateRepository->saveState($state);

                return;
            }
        }
    }

    public function calculateChampionshipPredictability(int $fromWeek): array
    {
        $totalWeek = $this->teamService->getTotalWeek();
        $currentStats = $this->getMatchesUntilWeek($fromWeek);
        $currentPoints = $this->sumPoints($currentStats);
        $remainingMatchesCount = [];
        $currentMatchResults = $this->getMatchResults();

        for ($week = $fromWeek; $week < $totalWeek; $week++) {
            $futureMatches = $currentMatchResults[(string) $week] ?? [];

            foreach ($futureMatches as $match) {
                $matchResult = MatchResultData::fromArray($match);
                $remainingMatchesCount[$matchResult->homeTeam->teamName] = ($remainingMatchesCount[$matchResult->homeTeam->teamName] ?? 0) + 1;
                $remainingMatchesCount[$matchResult->otherTeam->teamName] = ($remainingMatchesCount[$matchResult->otherTeam->teamName] ?? 0) + 1;
            }
        }

        $maxPossiblePoints = [];

        foreach ($this->teamService->getTeams() as $team) {
            $current = $currentPoints[$team->teamName] ?? 0;
            $remaining = $remainingMatchesCount[$team->teamName] ?? 0;
            $maxPossiblePoints[$team->teamName] = $current + ($remaining * 3);
            $currentPoints[$team->teamName] = $current;
        }

        arsort($currentPoints);
        $guaranteedChampion = array_key_first($currentPoints);

        if ($guaranteedChampion !== null) {
            $leaderPoints = $currentPoints[$guaranteedChampion];
            $isGuaranteed = collect($this->teamService->getTeams())
                ->reject(fn (TeamData $team) => $team->teamName === $guaranteedChampion)
                ->every(fn (TeamData $team) => $leaderPoints > ($maxPossiblePoints[$team->teamName] ?? 0));

            if ($isGuaranteed) {
                $probability = [];

                foreach ($this->teamService->getTeams() as $team) {
                    $probability[$team->teamName] = $team->teamName === $guaranteedChampion ? 100.0 : 0.0;
                }

                return $probability;
            }
        }

        $winCount = [];

        for ($i = 0; $i < LeagueConstants::MONTE_CARLO_MAX_ITERATION; $i++) {
            $simulatedMatchResults = json_decode(json_encode($currentMatchResults), true);
            $simulatedWeek = $fromWeek;

            while ($simulatedWeek < $totalWeek) {
                $this->playCurrentWeekInState($simulatedMatchResults, $simulatedWeek, $totalWeek);
            }

            $totalList = $this->sumPoints($this->getMatchesUntilWeekFromResults($simulatedMatchResults, $totalWeek));
            $winner = $this->findLeagueWinner($totalList);

            if ($winner !== null) {
                $winCount[$winner] = ($winCount[$winner] ?? 0) + 1;
            }
        }

        $probability = [];

        foreach ($winCount as $team => $wins) {
            $probability[$team] = ($wins * 100.0) / LeagueConstants::MONTE_CARLO_MAX_ITERATION;
        }

        return $probability;
    }

    private function playCurrentWeekInState(array &$matchResults, int &$currentWeek, int $totalWeek): void
    {
        if ($currentWeek >= $totalWeek) {
            return;
        }

        $weekMatches = $matchResults[(string) $currentWeek] ?? null;

        if (! is_array($weekMatches)) {
            $currentWeek++;

            return;
        }

        foreach ($weekMatches as $index => $match) {
            $matchResult = MatchResultData::fromArray($match);
            [$homeGoals, $awayGoals] = self::simulateMatchScore(
                $matchResult->homeTeam->strength,
                $matchResult->otherTeam->strength,
            );

            $matchResult->homeGoalCount = $homeGoals;
            $matchResult->otherTeamGoalCount = $awayGoals;
            MatchMath::calculateMatchResultOfTeams($matchResult);
            $weekMatches[$index] = $matchResult->toArray();
        }

        $matchResults[(string) $currentWeek] = $weekMatches;
        $currentWeek++;
    }

    public function getStandings(): array
    {
        $teams = array_map(fn (TeamData $team) => $team->clone(), $this->teamService->getTeams());
        $statsMap = $this->getMatchesUntilWeek($this->getCurrentWeek());

        $standings = array_map(function (TeamData $team) use ($statsMap) {
            $cloned = new TeamData($team->teamName, $team->strength);

            foreach ($statsMap[$team->teamName] ?? [] as $stat) {
                $cloned = $cloned->add($stat);
            }

            return $cloned;
        }, $teams);

        usort($standings, function (TeamData $a, TeamData $b) {
            $pointDiff = $b->totalPoint - $a->totalPoint;

            if ($pointDiff !== 0) {
                return $pointDiff;
            }

            return $b->average <=> $a->average;
        });

        return array_map(fn (TeamData $team) => $team->toArray(), $standings);
    }

    public static function simulateMatchScore(float $homeStrength, float $awayStrength): array
    {
        $maxGoals = LeagueConstants::MAX_TOTAL_GOAL_NUMBER;

        $safeHomeStrength = max(1.0, $homeStrength);
        $safeAwayStrength = max(1.0, $awayStrength);
        $normalizedHome = $safeHomeStrength / 100.0;
        $normalizedAway = $safeAwayStrength / 100.0;
        $strengthDiff = $homeStrength - $awayStrength;
        $strengthRatio = $safeHomeStrength / $safeAwayStrength;
        $inverseStrengthRatio = $safeAwayStrength / $safeHomeStrength;

        $homeAdvantage = 0.18;
        $balanceFactor = tanh($strengthDiff / 16.0);
        $matchTempo = 0.9 + (lcg_value() * 0.32);

        $homeExpected = (0.55 + (pow($normalizedHome, 1.18) * 1.95) + $homeAdvantage + max(0, $balanceFactor) * 1.05) * $matchTempo;
        $awayExpected = (0.42 + (pow($normalizedAway, 1.2) * 1.7) + max(0, -$balanceFactor) * 0.9) * $matchTempo;

        $defensiveSuppression = min(0.55, abs($strengthDiff) / 95.0);

        if ($strengthDiff > 0) {
            $awayExpected *= (1.0 - $defensiveSuppression);
        } elseif ($strengthDiff < 0) {
            $homeExpected *= (1.0 - ($defensiveSuppression * 0.9));
        }

        $homeExpected *= min(1.85, pow($strengthRatio, 0.2));
        $awayExpected *= min(1.85, pow($inverseStrengthRatio, 0.2));

        if ($strengthRatio >= 2.0) {
            $awayExpected *= 0.6;
            $homeExpected *= 1.12;
        }

        if ($inverseStrengthRatio >= 2.0) {
            $homeExpected *= 0.6;
            $awayExpected *= 1.12;
        }

        if ($safeHomeStrength <= 15 && $safeAwayStrength >= 25) {
            $homeExpected *= 0.52;
        }

        if ($safeAwayStrength <= 15 && $safeHomeStrength >= 25) {
            $awayExpected *= 0.52;
        }

        if ($safeHomeStrength <= 10 && $safeAwayStrength >= 30) {
            $homeExpected *= 0.35;
        }

        if ($safeAwayStrength <= 10 && $safeHomeStrength >= 30) {
            $awayExpected *= 0.35;
        }

        $homeExpected = max(0.15, $homeExpected);
        $awayExpected = max(0.05, $awayExpected);

        $homeGoals = min(MatchMath::poisson($homeExpected), $maxGoals);
        $awayGoals = min(MatchMath::poisson($awayExpected), $maxGoals);

        if (abs($strengthDiff) >= 15) {
            if ($strengthDiff > 0 && $homeGoals <= $awayGoals && lcg_value() < 0.72) {
                $homeGoals = min($maxGoals, $homeGoals + 1);
                $awayGoals = max(0, $awayGoals - (lcg_value() < 0.45 ? 1 : 0));
            } elseif ($strengthDiff < 0 && $awayGoals <= $homeGoals && lcg_value() < 0.72) {
                $awayGoals = min($maxGoals, $awayGoals + 1);
                $homeGoals = max(0, $homeGoals - (lcg_value() < 0.45 ? 1 : 0));
            }
        }

        if ($homeStrength >= 80 && $awayStrength <= 45 && lcg_value() < 0.28) {
            $homeGoals = min($maxGoals, $homeGoals + 1 + (lcg_value() < 0.4 ? 1 : 0));
        }

        if ($awayStrength >= 80 && $homeStrength <= 45 && lcg_value() < 0.24) {
            $awayGoals = min($maxGoals, $awayGoals + 1 + (lcg_value() < 0.35 ? 1 : 0));
        }

        if ($safeHomeStrength <= 8 && $safeAwayStrength >= 25) {
            $homeGoals = min($homeGoals, 1);
        }

        if ($safeAwayStrength <= 8 && $safeHomeStrength >= 25) {
            $awayGoals = min($awayGoals, 1);
        }

        if ($safeHomeStrength <= 5 && $safeAwayStrength >= 35 && lcg_value() < 0.82) {
            $homeGoals = 0;
        }

        if ($safeAwayStrength <= 5 && $safeHomeStrength >= 35 && lcg_value() < 0.82) {
            $awayGoals = 0;
        }

        return [$homeGoals, $awayGoals];
    }

    public function sumPoints(array $map): array
    {
        $totals = [];

        foreach ($map as $teamName => $teamStats) {
            foreach ($teamStats as $teamStat) {
                $totals[$teamName] = ($totals[$teamName] ?? 0) + $teamStat->totalPoint;
            }
        }

        return $totals;
    }

    private function addTeamToList(array &$lists, TeamData $team): void
    {
        $lists[$team->teamName] ??= [];
        $lists[$team->teamName][] = $team;
    }

    private function findBaseTeam(string $teamName): ?TeamData
    {
        foreach ($this->teamService->getTeams() as $team) {
            if (strcasecmp($team->teamName, $teamName) === 0) {
                return $team->clone();
            }
        }

        return null;
    }

    private function findLeagueWinner(array $totalList): ?string
    {
        if ($totalList === []) {
            return null;
        }

        arsort($totalList);

        return array_key_first($totalList);
    }
}
