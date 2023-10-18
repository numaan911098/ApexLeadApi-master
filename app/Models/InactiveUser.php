<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InactiveUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'delete_at',
        'delete_source'
    ];

    /**
     * Save an inactive user record.
     *
     * @param int $userId
     * @param string $deleteAt
     * @param string $source
     * @return InactiveUser|null
     */
    public function saveInactiveUser(int $userId, string $deleteAt, string $source): ?InactiveUser
    {
        return $this->firstOrCreate(
            ['user_id' => $userId],
            [
                'delete_at' => $deleteAt,
                'delete_source' => $source,
            ]
        );
    }

    /**
     * Get an inactive user record.
     *
     * @param int $userId
     * @param string $source
     * @return InactiveUser|null
     */
    public function getInactiveUser(int $userId, string $source): ?InactiveUser
    {
        return $this->where('user_id', $userId)
            ->where('delete_source', $source)
            ->first();
    }

    /**
     * Remove user account from deletion request
     *
     * @param int $userId
     * @param string $source
     * @return boolean
     */
    public function removeArchivedUser(int $userId, string $source): bool
    {
        return $this->where('user_id', $userId)
            ->where('delete_source', $source)->delete();
    }
}
