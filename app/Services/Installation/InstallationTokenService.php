<?php

declare(strict_types=1);

namespace App\Services\Installation;

use App\Models\InstallationToken;
use App\Models\Room;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class InstallationTokenService
{
    /**
     * Generate and store an installation token.
     */
    public function createToken(?string $roomId = null): string
    {
        $token = Str::random(64);

        if ($roomId) {
            try {
                // Verify room exists before creating token
                $room = Room::query()->findOrFail($roomId);

                InstallationToken::query()->create([
                    'token' => $token,
                    'room_id' => $roomId,
                    'expires_at' => now()->addDays(7), // Token valid for 7 days
                ]);
            } catch (Exception $e) {
                throw new ModelNotFoundException("Room with ID [{$roomId}] not found.");
            }
        }

        return $token;
    }
}
