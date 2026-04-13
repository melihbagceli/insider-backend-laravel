<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(private TeamService $teamService) {}

    public function index(): JsonResponse
    {
        return response()->json(array_map(
            fn ($team) => $team->toArray(),
            $this->teamService->getTeams()
        ), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(Request $request): JsonResponse
    {
        $teamName = trim((string) $request->input('teamName', ''));
        $strength = $request->input('strength');

        if ($teamName === '' || ! is_numeric($strength) || (int) $strength < 1 || (int) $strength > 100) {
            return response()->json('Geçersiz takım verisi.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        if (! $this->teamService->addTeam([
            'teamName' => $teamName,
            'strength' => (int) $strength,
        ])) {
            return response()->json('Bu isimde bir takım zaten mevcut.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json('Takım eklendi.', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function update(Request $request, string $teamName): JsonResponse
    {
        $newTeamName = trim((string) $request->input('teamName', ''));
        $strength = $request->input('strength');

        if (! is_numeric($strength) || (int) $strength < 1 || (int) $strength > 100) {
            return response()->json('Geçersiz güç değeri.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        if ($newTeamName === '') {
            return response()->json('Takım adı boş olamaz.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        if (! $this->teamService->updateTeam($teamName, $newTeamName, (int) $strength)) {
            return response()->json('Takım bulunamadı veya güç değeri geçersiz.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json('Takım güncellendi.', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function destroy(string $teamName): JsonResponse
    {
        if (! $this->teamService->deleteTeam($teamName)) {
            return response()->json('Takım bulunamadı.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json('Takım silindi.', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function destroyAll(): JsonResponse
    {
        $this->teamService->deleteAllTeams();

        return response()->json('Tüm takımlar silindi.', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function resetDefaults(): JsonResponse
    {
        $addedCount = $this->teamService->loadDefaultTeams();

        return response()->json([
            'addedCount' => $addedCount,
            'message' => $addedCount === 0
                ? 'Varsayılan takımlar zaten mevcut.'
                : 'Eksik varsayılan takımlar eklendi.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
