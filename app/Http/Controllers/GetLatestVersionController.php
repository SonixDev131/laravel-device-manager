<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AgentPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetLatestVersionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_version' => 'required|string',
        ]);

        $isLatest = AgentPackage::query()
            ->where('version', $validated['agent_version'])
            ->value('is_latest');

        $latest_version = AgentPackage::query()->where('is_latest', true)->value('version');

        return $isLatest ?
            response()->json(['is_latest' => true]) :
            response()->json(['is_latest' => false, 'latest_version' => $latest_version]);
    }
}
