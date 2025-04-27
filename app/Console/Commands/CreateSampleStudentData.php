<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use App\Models\Team;
use App\Models\ParentStudentRelationship;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSampleStudentData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-sample-student-data {parentEmail?} {count=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample student data and link to a parent account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the parent email from the argument or prompt for it
        $parentEmail = $this->argument('parentEmail');
        
        if (!$parentEmail) {
            $parentEmail = $this->ask('Enter parent email address:');
        }
        
        // Find the parent user
        $parentUser = User::where('email', $parentEmail)->first();
        
        if (!$parentUser) {
            $this->error("Parent user with email {$parentEmail} not found.");
            
            if ($this->confirm('Do you want to create a parent user with this email?', true)) {
                $parentName = $this->ask('Enter parent name:');
                $parentPassword = $this->secret('Enter password (or press enter for "password"):') ?: 'password';
                
                $parentUser = User::create([
                    'name' => $parentName,
                    'email' => $parentEmail,
                    'password' => Hash::make($parentPassword),
                ]);
                
                // Create a personal team for the parent user
                $team = $parentUser->ownedTeams()->create([
                    'name' => $parentUser->name . "'s Team",
                    'personal_team' => true,
                ]);
                
                // Set the parent role in the team
                $parentUser->teams()->attach($team, ['role' => 'parent']);
                $parentUser->switchTeam($team);
                
                $this->info("Created parent user {$parentName} with email {$parentEmail}");
            } else {
                return 1;
            }
        }
        
        // Get the number of students to create
        $count = (int) $this->argument('count');
        $this->info("Creating {$count} sample students and linking them to parent {$parentUser->name}");
        
        // Get the team
        $team = $parentUser->currentTeam;
        
        if (!$team) {
            $this->error("Parent user does not have a current team.");
            return 1;
        }
        
        $createdStudents = [];
        
        // Create sample students
        for ($i = 1; $i <= $count; $i++) {
            $studentName = "Student {$i} " . $parentUser->name;
            $studentEmail = "student{$i}." . strtolower(str_replace(' ', '', $parentUser->name)) . "@example.com";
            
            // Check if student with this email already exists
            $existingStudent = Student::where('email', $studentEmail)->first();
            
            if ($existingStudent) {
                $this->info("Student with email {$studentEmail} already exists. Linking to parent.");
                $student = $existingStudent;
            } else {
                // Create student
                $student = Student::create([
                    'team_id' => $team->id,
                    'name' => $studentName,
                    'email' => $studentEmail,
                    'student_id' => 'S' . str_pad($i, 5, '0', STR_PAD_LEFT),
                    'gender' => $i % 2 === 0 ? 'Male' : 'Female',
                    'birth_date' => now()->subYears(rand(10, 18))->subMonths(rand(1, 11))->subDays(rand(1, 28)),
                    'status' => 'active',
                    'phone' => '+1' . rand(100, 999) . rand(100, 999) . rand(1000, 9999),
                ]);
                
                $this->info("Created student {$studentName} with email {$studentEmail}");
            }
            
            // Link student to parent
            $existingRelationship = ParentStudentRelationship::where('user_id', $parentUser->id)
                ->where('student_id', $student->id)
                ->first();
                
            if (!$existingRelationship) {
                ParentStudentRelationship::create([
                    'user_id' => $parentUser->id,
                    'student_id' => $student->id,
                    'relationship_type' => $i === 1 ? 'mother' : ($i === 2 ? 'father' : 'guardian'),
                    'is_primary' => $i === 1,
                ]);
                
                $this->info("Linked student {$studentName} to parent {$parentUser->name}");
            } else {
                $this->info("Student {$studentName} already linked to parent {$parentUser->name}");
            }
            
            $createdStudents[] = $student;
        }
        
        $this->info("Sample student data creation completed successfully.");
        
        // Show summary
        $this->table(
            ['Name', 'Email', 'Student ID', 'Gender', 'Status'],
            collect($createdStudents)->map(fn ($student) => [
                $student->name,
                $student->email,
                $student->student_id,
                $student->gender,
                $student->status,
            ])
        );
        
        return 0;
    }
} 