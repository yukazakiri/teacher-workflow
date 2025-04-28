<?php

namespace App\Models;

use App\Models\ParentStudentRelationship;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use LaraZeus\Boredom\Concerns\HasBoringAvatar;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements
    FilamentUser,
    HasAvatar,
    HasTenants
{
    use HasApiTokens, LogsActivity;

    // use HasBoringAvatar;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles;
    use HasTeams;
    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    public $incrementing = false;

    protected $keyType = "string";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ["name", "email", "password", "workos_id", "avatar"];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        "password",
        "workos_id",
        "remember_token",
        "two_factor_recovery_codes",
        "two_factor_secret",
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(["name", "email"]);
    }
    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ["profile_photo_url"];

    /**
     * Get the attributes that should be cast.`
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
        ];
    }

    /**
     * Get the students associated with the user.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
    public function teamRole(Team $team): ?string
    {
        $membership = \Illuminate\Support\Facades\DB::table("team_user")
            ->where("team_id", $team->id)
            ->where("user_id", $this->id)
            ->first();
        return $membership?->role;
    }
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === "admin") {
            return $this->email === "marianolukkanit17@gmail.com";
        }

        return true;
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->allTeams();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->belongsToTeam($tenant);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_photo_url;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile_photo_url;
    }

    /**
     * Get the channels that the user is a member of.
     */
    public function channels()
    {
        return $this->belongsToMany(Channel::class, "channel_members")
            ->withPivot("permissions")
            ->withTimestamps();
    }

    /**
     * Get the messages that the user has sent.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the message reactions that the user has made.
     */
    public function messageReactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Get the messages where the user is mentioned.
     */
    public function mentions()
    {
        return $this->belongsToMany(
            Message::class,
            "message_mentions"
        )->withTimestamps();
    }

    public function currentTeam()
    {
        if (is_null($this->current_team_id) && $this->id) {
            $this->switchTeam($this->personalTeam());
        }

        return $this->belongsTo(Team::class, "current_team_id");
    }

    /**
     * Get the user's sessions.
     */
    public function sessions()
    {
        return $this->hasMany(Session::class, "user_id");
    }

    /**
     * Get the user's personal team.
     */
    public function personalTeam()
    {
        return $this->ownedTeams()->where("personal_team", true)->first();
    }

    /**
     * Get the students linked to this parent user.
     */
    public function linkedStudents()
    {
        return $this->hasManyThrough(
            Student::class,
            ParentStudentRelationship::class,
            "user_id", // Foreign key on ParentStudentRelationship
            "id", // Foreign key on Student
            "id", // Local key on User
            "student_id" // Local key on ParentStudentRelationship
        );
    }

    /**
     * Get parent-student relationships for this user.
     */
    public function parentStudentRelationships()
    {
        return $this->hasMany(ParentStudentRelationship::class, "user_id");
    }

    /**
     * Check if this user has any linked students (for parents).
     */
    public function hasLinkedStudents(): bool
    {
        return $this->parentStudentRelationships()->exists();
    }
}
