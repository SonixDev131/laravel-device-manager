<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateAllAgentsAction;
use App\Http\Requests\UpdateAgentsRequest;
use App\Models\Computer;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AgentsController extends Controller
{
    /**
     * Display the agents management page.
     */
    public function index(): Response
    {
        // Get computers summarized by status
        $computerStats = Computer::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Get total count
        $totalComputers = Computer::query()->count();

        // Get the 10 most recently active computers
        $recentComputers = Computer::query()
            ->with('room')
            ->orderByDesc('last_heartbeat_at')
            ->limit(10)
            ->get();

        return Inertia::render('agents/Index', [
            'stats' => [
                'total' => $totalComputers,
                'online' => $computerStats['online'] ?? 0,
                'idle' => $computerStats['idle'] ?? 0,
                'offline' => $computerStats['offline'] ?? 0,
                'timeout' => $computerStats['timeout'] ?? 0,
            ],
            'recentComputers' => $recentComputers,
        ]);
    }

    /**
     * Initiate update for all agents.
     */
    public function updateAll(UpdateAgentsRequest $request, UpdateAllAgentsAction $action): JsonResponse
    {
        $result = $action->handle($request->user(), $request->validated());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'total' => $result['total'],
                    'updated' => $result['updated'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'data' => [
                'total' => $result['total'] ?? 0,
                'updated' => $result['updated'] ?? 0,
                'failed' => $result['failed'] ?? 0,
            ],
        ], 500);
    }
}
