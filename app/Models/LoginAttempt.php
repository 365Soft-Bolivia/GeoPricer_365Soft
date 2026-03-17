<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\LoginAttempt
 *
 * @property int $id
 * @property string $email
 * @property string $ip_address
 * @property string|null $user_agent
 * @property bool $successful
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon $attempted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereSuccessful($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereFailureReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereAttemptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoginAttempt whereUpdatedAt($value)
 */
class LoginAttempt extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'successful',
        'failure_reason',
        'attempted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    /**
     * Scope para obtener intentos fallidos.
     */
    public function scopeFailed($query)
    {
        return $query->where('successful', false);
    }

    /**
     * Scope para obtener intentos exitosos.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('successful', true);
    }

    /**
     * Scope para obtener intentos por email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope para obtener intentos por dirección IP.
     */
    public function scopeByIpAddress($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope para obtener intentos recientes (últimos N minutos).
     */
    public function scopeRecent($query, int $minutes = 15)
    {
        return $query->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Contar intentos fallidos recientes para rate limiting.
     */
    public static function countRecentFailedAttempts(string $email, string $ipAddress, int $minutes = 15): int
    {
        return self::failed()
            ->byEmail($email)
            ->byIpAddress($ipAddress)
            ->recent($minutes)
            ->count();
    }

    /**
     * Obtener el último intento para un email e IP.
     */
    public static function getLatestAttempt(string $email, string $ipAddress): ?LoginAttempt
    {
        return self::byEmail($email)
            ->byIpAddress($ipAddress)
            ->latest('attempted_at')
            ->first();
    }
}