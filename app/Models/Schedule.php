<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Schedule extends Model
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
        'is_active',
    ];
    
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
    
    /**
     * Get the team that owns the schedule.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    
    /**
     * Get the schedule items for the schedule.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ScheduleItem::class);
    }
    
    /**
     * Get items for a specific day of the week.
     */
    public function getItemsByDay(string $day): HasMany
    {
        return $this->items()->where('day_of_week', $day);
    }
}
