<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Infrastructure\Models\UserModel;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        UserModel::factory()->withRole([Role::ADMIN])->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        UserModel::factory()->unverified()->create([
            'name' => 'Pending User',
            'email' => 'pending@example.com',
        ]);
    }
}
