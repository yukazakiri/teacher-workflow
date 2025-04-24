<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Student;
use App\Models\Team;
use App\Models\User;

class StudentHelper
{
    /**
     * Create a student record for a user in a team.
     * If a student record already exists, it will be reactivated if needed.
     */
    public static function createStudentRecord(User $user, Team $team): Student
    {
        // Check if student record already exists
        $existingStudent = Student::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
        
        if ($existingStudent) {
            // If the student exists but is inactive, reactivate
            if ($existingStudent->status !== 'active') {
                $existingStudent->update(['status' => 'active']);
            }
            return $existingStudent;
        }
        
        // Generate a unique student ID
        $studentId = 'S' . now()->format('ymd') . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Create a new student record
        return Student::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => $studentId,
            'status' => 'active',
        ]);
    }
} 