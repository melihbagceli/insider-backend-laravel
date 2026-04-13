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
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $teamName = trim((string) $request->input('teamName', ''));
        $strength = $request->input('strength');

        if ($teamName === '' || ! is_numeric($strength) || (int) $strength < 1 || (int) $strength > 100) {
            return response()->json('Geçersiz takım verisi.', 400);
        }

        if (! $this->teamService->addTeam([
            'teamName' => $teamName,
            'strength' => (int) $strength,
        ])) {
            return response()->json('Bu isimde bir takım zaten mevcut.', 400);
        }

        return response()->json('Takım eklendi.');
    }

    public function update(Request $request, string $teamName): JsonResponse
    {
        $newTeamName = trim((string) $request->input('teamName', ''));
        $strength = $request->input('strength');

        if (! is_numeric($strength) || (int) $strength < 1 || (int) $strength > 100) {
            return response()->json('Geçersiz güç değeri.', 400);
        }

        if ($newTeamName === '') {
            return response()->json('Takım adı boş olamaz.', 400);
        }

        if (! $this->teamService->updateTeam($teamName, $newTeamName, (int) $strength)) {
            return response()->json('Takım bulunamadı veya güç değeri geçersiz.', 400);
        }

        return response()->json('Takım güncellendi.');
    }

    public function destroy(string $teamName): JsonResponse
    {
        if (! $this->teamService->deleteTeam($teamName)) {
            return response()->json('Takım bulunamadı.', 400);
        }

        return response()->json('Takım silindi.');
    }

    public function destroyAll(): JsonResponse
    {
        $this->teamService->deleteAllTeams();

        return response()->json('Tüm takımlar silindi.');
    }

    public function resetDefaults(): JsonResponse
    {
        $addedCount = $this->teamService->addMissingDefaultTeams();

        return response()->json([
            'addedCount' => $addedCount,
            'message' => $addedCount === 0
                ? 'Varsayılan takımlar zaten mevcut.'
                : 'Eksik varsayılan takımlar eklendi.',
        ]);
    }
}
