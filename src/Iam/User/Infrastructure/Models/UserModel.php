<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Models;

use Database\Factories\UserModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property Carbon|null $verified_at
 * @property string|null $verification_token
 * @property Carbon|null $verification_expires_at
 */
final class UserModel extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserModelFactory> */
    use HasFactory;

    use Notifiable;

    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function newFactory(): UserModelFactory
    {
        return UserModelFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'verified_at',
        'verification_token',
        'verification_expires_at',
    ];

    protected $hidden = [
        'password',
        'verification_token',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'verification_expires_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<UserRoleModel, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(UserRoleModel::class, 'user_id');
    }
}
