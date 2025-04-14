<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelPermission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'role',
        'permissions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
    ];
    
    /**
     * Default permission sets available in the system.
     */
    public const DEFAULT_PERMISSIONS = [
        'owner' => [
            'read' => true,
            'write' => true,
            'manage_messages' => true,
            'manage_channels' => true,
            'manage_categories' => true,
            'manage_members' => true,
        ],
        'admin' => [
            'read' => true,
            'write' => true,
            'manage_messages' => true,
            'manage_channels' => true,
            'manage_categories' => false,
            'manage_members' => true,
        ],
        'moderator' => [
            'read' => true,
            'write' => true,
            'manage_messages' => true,
            'manage_channels' => false,
            'manage_categories' => false,
            'manage_members' => false,
        ],
        'member' => [
            'read' => true,
            'write' => true,
            'manage_messages' => false,
            'manage_channels' => false,
            'manage_categories' => false,
            'manage_members' => false,
        ],
        'readonly' => [
            'read' => true,
            'write' => false,
            'manage_messages' => false,
            'manage_channels' => false,
            'manage_categories' => false,
            'manage_members' => false,
        ],
    ];

    /**
     * Get the team that owns the permission.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    
    /**
     * Get default permissions for a role.
     */
    public static function getDefaultPermissions(string $role): array
    {
        return self::DEFAULT_PERMISSIONS[$role] ?? self::DEFAULT_PERMISSIONS['member'];
    }
    
    /**
     * Check if a permission exists for a user in a team.
     */
    public static function hasPermission(User $user, Team $team, string $permission): bool
    {
        // Team owner has all permissions
        if ($team->user_id === $user->id) {
            return true;
        }
        
        // Get team member record
        $teamMember = $team->members()->where('user_id', $user->id)->first();
        
        if (!$teamMember) {
            return false;
        }
        
        // Get user role (from pivot or default to 'member')
        $role = $teamMember->pivot->role ?? 'member';
        
        // Get permissions for this role
        $rolePermissions = self::where('team_id', $team->id)
            ->where('role', $role)
            ->first();
            
        if (!$rolePermissions) {
            // Use default permissions if no custom permissions exist
            return self::getDefaultPermissions($role)[$permission] ?? false;
        }
        
        return $rolePermissions->permissions[$permission] ?? false;
    }
}
