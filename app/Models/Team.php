<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;
use Illuminate\Support\Facades\Route;

class Team extends JetstreamTeam
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    use HasUuids;

    public const GRADING_SYSTEM_SHS = 'shs';

    public const GRADING_SYSTEM_COLLEGE = 'college';

    public const COLLEGE_SCALE_GWA_5_POINT = 'gwa_5_point';

    public const COLLEGE_SCALE_GWA_4_POINT = 'gwa_4_point';

    public const COLLEGE_SCALE_GWA_PERCENTAGE = 'gwa_percentage';

    public const COLLEGE_SCALE_TERM_5_POINT = 'term_5_point'; // Term Based + 5 Point Scale

    public const COLLEGE_SCALE_TERM_4_POINT = 'term_4_point'; // Term Based + 4 Point Scale

    public const COLLEGE_SCALE_TERM_PERCENTAGE = 'term_percentage'; // Term Based + Percentage Scale

    // Map for easier checking
    public const COLLEGE_TERM_SCALES = [
        self::COLLEGE_SCALE_TERM_5_POINT,
        self::COLLEGE_SCALE_TERM_4_POINT,
        self::COLLEGE_SCALE_TERM_PERCENTAGE,
    ];

    public const COLLEGE_GWA_SCALES = [
        self::COLLEGE_SCALE_GWA_5_POINT,
        self::COLLEGE_SCALE_GWA_4_POINT,
        self::COLLEGE_SCALE_GWA_PERCENTAGE,
    ];

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
        'grading_system_type',
        'college_grading_scale', // Now includes GWA/Term distinction
        'shs_ww_weight',
        'shs_pt_weight',
        'shs_qa_weight',
        'college_prelim_weight', // Added
        'college_midterm_weight', // Added
        'college_final_weight', // Added
        'onboarding_step',
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
            'shs_ww_weight' => 'integer',
            'shs_pt_weight' => 'integer',
            'shs_qa_weight' => 'integer',
            'college_prelim_weight' => 'integer',
            'college_midterm_weight' => 'integer',
            'college_final_weight' => 'integer',
            'onboarding_step' => 'integer',
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
     * Get the team join QR codes for the team.
     */
    public function teamJoinQrCodes(): HasMany
    {
        return $this->hasMany(TeamJoinQrCode::class);
    }

    /**
     * Get the channel categories for the team.
     */
    public function channelCategories(): HasMany
    {
        return $this->hasMany(ChannelCategory::class);
    }

    /**
     * Get the channels for the team.
     */
    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    /**
     * Check if the team has a user with a specific role.
     */
    public function hasUserWithRole(User $user, string $role): bool
    {
        $teamMember = $this->users()->where('user_id', $user->id)->first();

        if (! $teamMember) {
            return false;
        }

        return $teamMember->membership->role === $role;
    }

    public function userIsOwner(User $user): bool
    {
        return $this->owner->is($user);
    }

    public function usesShsGrading(): bool
    {
        return $this->grading_system_type === self::GRADING_SYSTEM_SHS;
    }

    /**
     * Check if the team uses the College grading system.
     */
    public function usesCollegeGrading(): bool
    {
        return $this->grading_system_type === self::GRADING_SYSTEM_COLLEGE;
    }

    public function usesCollegeTermGrading(): bool
    {
        return $this->usesCollegeGrading() &&
            in_array($this->college_grading_scale, self::COLLEGE_TERM_SCALES);
    }

    /**
     * Get the underlying numeric scale (5_point, 4_point, percentage) for college, regardless of GWA/Term.
     */
    public function getCollegeNumericScale(): ?string
    {
        if (! $this->usesCollegeGrading()) {
            return null;
        }

        return match ($this->college_grading_scale) {
            self::COLLEGE_SCALE_GWA_5_POINT,
            self::COLLEGE_SCALE_TERM_5_POINT => '5_point',
            self::COLLEGE_SCALE_GWA_4_POINT,
            self::COLLEGE_SCALE_TERM_4_POINT => '4_point',
            self::COLLEGE_SCALE_GWA_PERCENTAGE,
            self::COLLEGE_SCALE_TERM_PERCENTAGE => 'percentage',
            default => null,
        };
    }

    public function usesCollegeGwaGrading(): bool
    {
        return $this->usesCollegeGrading() &&
            in_array($this->college_grading_scale, self::COLLEGE_GWA_SCALES);
    }

    /**
     * Get the team's grading system description.
     */
    public function gradingSystemDescription(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->usesShsGrading()) {
                    return 'K-12 SHS (Component Weighted)';
                }
                if ($this->usesCollegeGrading()) {
                    $desc = 'College/University';
                    $scale_part = match ($this->getCollegeNumericScale()) {
                        '5_point' => '5-Point',
                        '4_point' => '4-Point',
                        'percentage' => 'Percentage',
                        default => 'Scale Not Set',
                    };
                    if ($this->usesCollegeTermGrading()) {
                        $desc .=
                            ' (Term-Based: Prelim, Midterm, Final / '.
                            $scale_part.
                            ')';
                    } elseif ($this->usesCollegeGwaGrading()) {
                        $desc .= ' (GWA Based / '.$scale_part.')';
                    } else {
                        $desc .= ' (Configuration Incomplete)';
                    }

                    return $desc;
                }

                return 'Not Configured';
            }
        );
    }

    /**
     * Get the URL for joining the team via its code.
     */
    public function joinUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => route('teams.join.show', ['join_code' => $this->join_code])
        );
    }
}
