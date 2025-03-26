<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'student_id',
        'created_by',
        'status',
        'date',
        'time_in',
        'time_out',
        'qr_verified',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'datetime',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'qr_verified' => 'boolean',
    ];

    /**
     * Get the team that the attendance record belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the student that the attendance record belongs to.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the user who created the attendance record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the student is present.
     */
    public function isPresent(): bool
    {
        return $this->status === 'present';
    }

    /**
     * Check if the student is absent.
     */
    public function isAbsent(): bool
    {
        return $this->status === 'absent';
    }

    /**
     * Check if the student is late.
     */
    public function isLate(): bool
    {
        return $this->status === 'late';
    }

    /**
     * Check if the student's absence is excused.
     */
    public function isExcused(): bool
    {
        return $this->status === 'excused';
    }

    /**
     * Scope a query to only include attendances for a specific team.
     */
    public function scopeForTeam($query, Team $team)
    {
        return $query->where('team_id', $team->id);
    }

    /**
     * Scope a query to only include attendances for a specific date.
     */
    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope a query to only include attendances for a specific student.
     */
    public function scopeForStudent($query, Student $student)
    {
        return $query->where('student_id', $student->id);
    }
}
