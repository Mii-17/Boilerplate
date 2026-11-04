<?php

declare(strict_types=1);

namespace Src\Iam\User\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Iam\User\Domain\Enums\Role;

/**
 * @property string $user_id
 * @property Role $role
 */
final class UserRoleModel extends Model
{
    protected $table = 'role_user';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return ['role' => Role::class];
    }

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class);
    }
}
