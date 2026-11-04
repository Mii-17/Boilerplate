<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\ValueObjects\VerificationToken;
use Src\Iam\User\Infrastructure\Models\UserModel;

/**
 * @extends Factory<UserModel>
 */
class UserModelFactory extends Factory
{
    protected $model = UserModel::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Uuid::uuid7(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'verified_at' => now(),
            'verification_token' => null,
            'verification_expires_at' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'verified_at' => null,
            'verification_token' => VerificationToken::generate()->value(),
            'verification_expires_at' => now()->addDay(),
        ]);
    }

    /**
     * @param  list<Role>  $roles
     */
    public function withRole(array $roles = [Role::USER]): static
    {
        return $this->afterCreating(function (UserModel $user) use ($roles): void {
            $user->roles()->delete();
            foreach ($roles as $role) {
                $user->roles()->create(['role' => $role]);
            }
        });
    }

    public function configure(): static
    {
        return $this->afterCreating(function (UserModel $user): void {
            $user->roles()->create(['role' => Role::USER]);
        });
    }
}
