<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\RolesEnum;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class EnsureUserHasRoomAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (BaseResponse)  $next
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // SuperAdmins have access to all rooms
        if ($user->hasRole(RolesEnum::SUPERADMIN->value)) {
            return $next($request);
        }

        // Get room ID from route parameter
        $roomId = $request->route('room')?->id ?? $request->route('roomId') ?? $request->input('room_id');

        if (! $roomId) {
            return response()->json(['message' => 'Room ID is required'], Response::HTTP_BAD_REQUEST);
        }

        // Check if user has access to this specific room
        if (! $user->hasAccessToRoom($roomId)) {
            return response()->json([
                'message' => 'You do not have access to this room',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
