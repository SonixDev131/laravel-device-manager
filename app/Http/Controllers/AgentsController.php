<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateAllAgentsAction;
use App\Http\Requests\UpdateAgentsRequest;
use App\Http\Requests\UploadAgentRequest;
use App\Models\AgentPackage;
use App\Models\Computer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

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

    public function uploadPackage(UploadAgentRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $file = $validated['file'];
            $version = $validated['version'];

            Storage::putFileAs('updates', $file, "{$version}.zip");

            AgentPackage::query()->where('is_latest', true)->update(['is_latest' => false]);
            AgentPackage::query()->create([
                'name' => "{$version}.zip",
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'path' => "updates/{$version}.zip",
                'disk' => config('app.uploads.disk'),
                'file_hash' => hash_file(config('app.uploads.hash'), Storage::path("updates/{$version}.zip")),
                'version' => $version,
                'is_latest' => true,
                'size' => $file->getSize(),
            ]);

            return redirect()->back()->with('success', 'Agent package uploaded successfully');
        } catch (Throwable $e) {
            Storage::delete("updates/{$version}.zip");
            Log::error("Agent package upload error: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Upload failed: '.$e->getMessage());
        }
    }
}
