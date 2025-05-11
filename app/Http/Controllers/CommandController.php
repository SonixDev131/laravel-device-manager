<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class CommandController extends Controller
{
    /**
     * Publish a message to RabbitMQ
     */
    public function publish(
        CreateMessageRequest $request,
        PublishMessageAction $action
    ): JsonResponse {
        $action->handle(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Message has been published successfully',
        ]);
    }
}
