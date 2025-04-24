<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TeamJoinQrCode extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'team_id',
        'created_by',
        'code',
        'description',
        'expires_at',
        'is_active',
        'use_limit',
        'use_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'use_limit' => 'integer',
        'use_count' => 'integer',
    ];

    /**
     * Get the team associated with the QR code.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created the QR code.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine if the QR code is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Determine if the QR code is valid for use.
     */
    public function isValid(): bool
    {
        // Valid if active, not expired, and under use limit (if set)
        return $this->is_active && 
               !$this->isExpired() && 
               ($this->use_limit === null || $this->use_count < $this->use_limit);
    }

    /**
     * Deactivate this QR code.
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Extend the expiry time of this QR code.
     */
    public function extendExpiry(int $minutes): bool
    {
        $this->expires_at = $this->expires_at->addMinutes($minutes);
        return $this->save();
    }

    /**
     * Record usage of this QR code.
     */
    public function recordUsage(): bool
    {
        $this->use_count++;
        
        // If we've reached the use limit, deactivate the code
        if ($this->use_limit !== null && $this->use_count >= $this->use_limit) {
            $this->is_active = false;
        }
        
        return $this->save();
    }

    /**
     * Create a new QR code for a team.
     */
    public static function createForTeam(
        Team $team,
        User $creator,
        int $expiryMinutes = 60,
        ?string $description = null,
        ?int $useLimit = null
    ): self {
        // Deactivate any existing active QR codes for this team
        self::where('team_id', $team->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Generate a unique code
        $code = Str::random(32);

        // Create and return a new QR code
        return self::create([
            'team_id' => $team->id,
            'created_by' => $creator->id,
            'code' => $code,
            'description' => $description ?? "Join {$team->name}",
            'expires_at' => now()->addMinutes($expiryMinutes),
            'is_active' => true,
            'use_limit' => $useLimit,
            'use_count' => 0,
        ]);
    }
} 