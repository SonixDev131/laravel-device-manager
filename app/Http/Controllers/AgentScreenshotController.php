<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StoreScreenshotAction;
use App\Http\Requests\Api\Agent\AgentScreenshotRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AgentScreenshotController extends Controller
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        private readonly StoreScreenshotAction $storeScreenshotAction
    ) {}

    /**
     * Handle screenshot upload from agent
     */
    public function store(AgentScreenshotRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            Log::info('Screenshot upload received', [
                'command_id' => $validated['command_id'],
                'computer_id' => $validated['computer_id'],
                'file_size' => $request->file('screenshot')->getSize(),
                'mime_type' => $request->file('screenshot')->getMimeType(),
            ]);

            $screenshot = $this->storeScreenshotAction->handle([
                'command_id' => $validated['command_id'],
                'computer_id' => $validated['computer_id'],
                'screenshot' => $request->file('screenshot'),
                'taken_at' => $validated['taken_at'],
            ]);

            Log::info('Screenshot stored successfully', [
                'screenshot_id' => $screenshot->id,
                'file_path' => $screenshot->file_path,
            ]);

            return response()->json([
                'message' => 'Screenshot uploaded successfully',
                'screenshot_id' => $screenshot->id,
                'file_url' => $screenshot->file_url,
            ], Response::HTTP_CREATED);

        } catch (Throwable $e) {
            Log::error('Screenshot upload failed', [
                'command_id' => $validated['command_id'] ?? null,
                'computer_id' => $validated['computer_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to upload screenshot',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
