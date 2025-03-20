<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ResourceCategory extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'description',
        'color',
        'icon',
        'sort_order',
        'type', // 'teacher_material' or 'student_resource'
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the team that owns the category.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the resources for this category.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(ClassResource::class, 'category_id');
    }
    
    /**
     * Determine if this category is for teacher materials.
     */
    public function isTeacherMaterial(): bool
    {
        return $this->type === 'teacher_material';
    }
    
    /**
     * Determine if this category is for student resources.
     */
    public function isStudentResource(): bool
    {
        return $this->type === 'student_resource';
    }
} 