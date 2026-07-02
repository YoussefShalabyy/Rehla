<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@vistastay.com'],
            [
                'uuid'              => (string) Str::uuid(),
                'name'              => 'Platform Admin',
                'password'          => Hash::make('password'),
                'role'              => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );
    }
}
