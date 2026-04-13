<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MatchService;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(
        private MatchService $matchService,
        private TeamService $teamService,
    ) {}

    public function generateFixtures(): JsonResponse
    {
        $teamCount = count($this->teamService->getTeams());

        if ($teamCount < 4) {
            return response()->json('En az 4 takım gereklidir.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        if ($teamCount % 2 !== 0) {
            return response()->json('Takım sayısı çift olmalıdır.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json($this->matchService->generateLeagueMatches(), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function playCurrentWeek(): JsonResponse
    {
        $totalWeek = $this->teamService->getTotalWeek();
        $currentWeek = $this->matchService->getCurrentWeek();

        if ($currentWeek >= $totalWeek) {
            return response()->json('Tüm haftalar oynanmıştır.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        $weekMatches = $this->matchService->playCurrentWeek();

        if ($weekMatches === null) {
            return response()->json('Hafta oynanamadı.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'matches' => $weekMatches,
            'week' => $currentWeek,
            'totalWeek' => $totalWeek,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getStandings(): JsonResponse
    {
        return response()->json($this->matchService->getStandings(), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function playAllWeeks(): JsonResponse
    {
        $totalWeek = $this->teamService->getTotalWeek();
        $currentWeek = $this->matchService->getCurrentWeek();

        if ($currentWeek >= $totalWeek) {
            return response()->json('Tüm haftalar zaten oynanmıştır.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'standings' => $this->matchService->playAllWeeks(),
            'totalWeek' => $totalWeek,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getChampionshipPredictions(): JsonResponse
    {
        return response()->json(
            $this->matchService->calculateChampionshipPredictability($this->matchService->getCurrentWeek()),
            200, [], JSON_UNESCAPED_UNICODE
        );
    }

    public function updateMatchResult(Request $request): JsonResponse
    {
        try {
            $week = (int) $request->input('week');
            $homeTeam = (string) $request->input('homeTeam');
            $otherTeam = (string) $request->input('otherTeam');
            $homeGoals = (int) $request->input('homeGoalCount');
            $otherGoals = (int) $request->input('otherTeamGoalCount');

            $this->matchService->updateMatchResult($week, $homeTeam, $otherTeam, $homeGoals, $otherGoals);

            return response()->json($this->matchService->getStandings(), 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $exception) {
            return response()->json('Maç sonucu güncellenemedi: '.$exception->getMessage(), 400, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
