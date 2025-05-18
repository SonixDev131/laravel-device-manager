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
        $latest_version = AgentPackage::query()->where('is_latest', true)->value('version');

        return response()->json(['latest_version' => $latest_version]);
    }
}
