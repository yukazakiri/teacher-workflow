<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StudentHelper
{
    /**
     * Create a student record for a user in a team if one doesn't exist
     */
    public static function createStudentRecord(User $user, Team $team): ?Student
    {
        // Only create student record if the user has the student role
        $membership = \DB::table('team_user')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
            
        if (!$membership || $membership->role !== 'student') {
            return null;
        }
        
        // Check if student record already exists
        $existing = Student::where('user_id', $user->id)
            ->where('team_id', $team->id)
            ->first();
            
        if ($existing) {
            return $existing;
        }
        
        try {
            // Create a new student record
            $student = Student::create([
                'user_id' => $user->id,
                'team_id' => $team->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
            
            Log::info('Student record created', [
                'student_id' => $student->id,
                'user_id' => $user->id,
                'team_id' => $team->id,
            ]);
            
            return $student;
        } catch (\Exception $e) {
            Log::error('Failed to create student record', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'team_id' => $team->id,
            ]);
            
            return null;
        }
    }
} 