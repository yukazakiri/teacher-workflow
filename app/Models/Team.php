<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Team extends JetstreamTeam
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'personal_team',
        'join_code',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate a join code when creating a new team
        static::creating(function ($team) {
            if (empty($team->join_code)) {
                $team->generateJoinCode();
            }
        });
    }

    /**
     * Generate a unique 6-character join code for the team.
     */
    public function generateJoinCode(): void
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('join_code', $code)->exists());

        $this->join_code = $code;
    }

    /**
     * Get the exams for the team.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get the activities for the team.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the students for the team.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the owner of the team.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the resource categories for the team.
     */
    public function resourceCategories(): HasMany
    {
        return $this->hasMany(ResourceCategory::class);
    }

    /**
     * Get the class resources for the team.
     */
    public function classResources(): HasMany
    {
        return $this->hasMany(ClassResource::class);
    }

    /**
     * Get the schedules for the team.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the attendances for the team.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the attendance QR codes for the team.
     */
    public function attendanceQrCodes(): HasMany
    {
        return $this->hasMany(AttendanceQrCode::class);
    }

    /**
     * Check if the team has a user with a specific role.
     */
    public function hasUserWithRole(User $user, string $role): bool
    {
        $teamMember = $this->users()->where('user_id', $user->id)->first();

        if (!$teamMember) {
            return false;
        }

        return $teamMember->membership->role === $role;
    }

    public function userIsOwner(User $user): bool
    {
        return $this->owner->is($user);
    }
}
