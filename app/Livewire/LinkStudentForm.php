<?php

namespace App\Livewire;

use App\Models\ParentStudentRelationship;
use App\Models\Student;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LinkStudentForm extends Component
{
    public bool $showModal = false;
    public string $studentId = '';
    public ?string $error = null;

    protected $rules = [
        'studentId' => 'required|string|max:50',
    ];

    protected $listeners = ['showLinkStudentForm' => 'showForm'];

    public function mount()
    {
        // Check if the user is a parent and has no linked students
        $user = Auth::user();
        $team = $user?->currentTeam;
        
        if ($team) {
            $membership = \Illuminate\Support\Facades\DB::table('team_user')
                ->where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->first();
            
            // Only show for parent users without any linked students
            if ($membership && $membership->role === 'parent' && !$user->hasLinkedStudents()) {
                $this->showModal = true;
            }
        }
    }

    public function linkStudent()
    {
        $this->validate();
        
        $this->error = null;
        $user = Auth::user();
        $team = $user?->currentTeam;
        
        if (!$team) {
            $this->error = 'You must be part of a team to link a student.';
            return;
        }
        
        // Find the student by student_id in the current team
        $student = Student::where('student_id', $this->studentId)
            ->where('team_id', $team->id)
            ->first();
        
        if (!$student) {
            $this->error = 'No student found with that ID in your current class.';
            return;
        }
        
        // Check if relationship already exists
        $existingRelation = ParentStudentRelationship::where('user_id', $user->id)
            ->where('student_id', $student->id)
            ->first();
            
        if ($existingRelation) {
            $this->error = 'You are already linked to this student.';
            return;
        }
        
        // Create the relationship
        ParentStudentRelationship::create([
            'user_id' => $user->id,
            'student_id' => $student->id,
            'relationship_type' => 'parent',
            'is_primary' => true, // First link is primary
        ]);
        
        // Show success notification
        Notification::make()
            ->title('Student linked successfully')
            ->success()
            ->send();
            
        // Close the modal
        $this->showModal = false;
        
        // Refresh the page to show student data
        $this->redirect(request()->header('Referer'));
    }

    public function showForm()
    {
        $this->showModal = true;
    }

    public function render()
    {
        return view('livewire.link-student-form');
    }
}
