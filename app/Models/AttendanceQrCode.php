<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AttendanceQrCode extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'created_by',
        'code',
        'date',
        'expires_at',
        'is_active',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the team that the QR code belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created the QR code.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the QR code is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the QR code is valid.
     */
    public function isValid(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    /**
     * Generate a new unique QR code.
     */
    public static function generateUniqueCode(): string
    {
        do {
            // Generate 32 bytes (256 bits) of random data for strong entropy
            $randomData = random_bytes(32);

            // Convert to URL-safe base64 without padding (remove = chars)
            $code = rtrim(strtr(base64_encode($randomData), '+/', '-_'), '=');

            // Ensure we only keep URL-safe characters (extra precaution)
            $code = preg_replace('/[^a-zA-Z0-9_-]/', '', $code);

            // Make sure we still have enough characters (at least 40) for uniqueness
            if (strlen($code) < 40) {
                continue;
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create a new QR code for a team.
     */
    public static function createForTeam(Team $team, User $user, Carbon $date, int $expiryMinutes = 30, ?string $description = null): self
    {
        return self::create([
            'team_id' => $team->id,
            'created_by' => $user->id,
            'code' => self::generateUniqueCode(),
            'date' => $date,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'is_active' => true,
            'description' => $description,
        ]);
    }

    /**
     * Deactivate the QR code.
     */
    public function deactivate(): self
    {
        $this->update(['is_active' => false]);

        return $this;
    }

    /**
     * Extend the expiry time of the QR code.
     */
    public function extendExpiry(int $minutes): self
    {
        $this->update([
            'expires_at' => Carbon::parse($this->expires_at)->addMinutes($minutes),
        ]);

        return $this;
    }
}
