<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateTeacherAction
{
    /**
     * Create a new teacher account.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create the user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Assign teacher role
            $user->assignRole(RolesEnum::TEACHER->value);

            return $user;
        });
    }
}
