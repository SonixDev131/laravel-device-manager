<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CommandStatus;
use App\Http\Requests\Api\Agent\AgentCommandResultRequest;
use App\Models\Command;
use Illuminate\Http\JsonResponse;

class AgentCommandResultController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AgentCommandResultRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $command_id = $validated['command_id'];
        $completed_at = $validated['completed_at'];
        $error = $validated['error'];

        $command = Command::findOrFail($command_id);

        if ($error) {
            $command->update([
                'completed_at' => $completed_at,
                'error' => $error,
                'status' => CommandStatus::FAILED,
            ]);
        } else {
            $command->update([
                'completed_at' => $completed_at,
                'status' => CommandStatus::COMPLETED,
            ]);
        }

        return response()->json(['message' => 'Command result received']);
    }
}
