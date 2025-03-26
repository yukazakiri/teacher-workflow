<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\AttendanceQrCode;
use App\Models\Student;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceManager extends Component
{
    use WithPagination;

    public Team $team;
    public string $date;
    public Collection $students;
    public array $attendance = [];
    public string $selectedStatus = 'present';
    public ?string $notes = null;
    public bool $showQrCode = false;
    public ?AttendanceQrCode $activeQrCode = null;
    public int $qrCodeExpiryMinutes = 30;
    public string $qrCodeDescription = '';
    public array $stats = [
        'total_students' => 0,
        'present' => 0,
        'absent' => 0,
        'late' => 0,
        'excused' => 0,
        'unrecorded' => 0,
    ];

    protected $listeners = ['refreshAttendance' => '$refresh'];

    protected $rules = [
        'attendance.*.student_id' => 'required|exists:students,id',
        'attendance.*.status' => 'required|in:present,absent,late,excused',
        'attendance.*.time_in' => 'nullable',
        'attendance.*.time_out' => 'nullable',
        'attendance.*.notes' => 'nullable|string',
    ];

    public function mount(Team $team)
    {
        $this->team = $team;
        $this->date = now()->toDateString();
        $this->loadStudents();
        $this->loadAttendance();
        $this->loadStats();
        $this->checkActiveQrCode();
    }

    public function loadStudents()
    {
        $this->students = $this->team->students()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function loadAttendance()
    {
        $existingAttendance = Attendance::where('team_id', $this->team->id)
            ->whereDate('date', $this->date)
            ->get()
            ->keyBy('student_id');

        $this->attendance = [];

        foreach ($this->students as $student) {
            if ($existingAttendance->has($student->id)) {
                $record = $existingAttendance->get($student->id);
                $this->attendance[$student->id] = [
                    'student_id' => $student->id,
                    'status' => $record->status,
                    'time_in' => $record->time_in ? $record->time_in->format('H:i') : null,
                    'time_out' => $record->time_out ? $record->time_out->format('H:i') : null,
                    'notes' => $record->notes,
                    'id' => $record->id,
                    'qr_verified' => $record->qr_verified,
                ];
            } else {
                $this->attendance[$student->id] = [
                    'student_id' => $student->id,
                    'status' => null,
                    'time_in' => null,
                    'time_out' => null,
                    'notes' => null,
                    'id' => null,
                    'qr_verified' => false,
                ];
            }
        }
    }

    public function loadStats()
    {
        $totalStudents = $this->students->count();
        $presentCount = Attendance::where('team_id', $this->team->id)
            ->whereDate('date', $this->date)
            ->where('status', 'present')
            ->count();
        $absentCount = Attendance::where('team_id', $this->team->id)
            ->whereDate('date', $this->date)
            ->where('status', 'absent')
            ->count();
        $lateCount = Attendance::where('team_id', $this->team->id)
            ->whereDate('date', $this->date)
            ->where('status', 'late')
            ->count();
        $excusedCount = Attendance::where('team_id', $this->team->id)
            ->whereDate('date', $this->date)
            ->where('status', 'excused')
            ->count();

        // Calculate unrecorded students
        $unrecordedCount = $totalStudents - $presentCount - $absentCount - $lateCount - $excusedCount;

        $this->stats = [
            'total_students' => $totalStudents,
            'present' => $presentCount,
            'absent' => $absentCount,
            'late' => $lateCount,
            'excused' => $excusedCount,
            'unrecorded' => $unrecordedCount,
        ];
    }

    public function checkActiveQrCode()
    {
        $this->activeQrCode = AttendanceQrCode::where('team_id', $this->team->id)
            ->where('date', $this->date)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public function updateDate($newDate)
    {
        $this->date = $newDate;
        $this->loadAttendance();
        $this->loadStats();
        $this->checkActiveQrCode();
    }

    public function markAttendance($studentId, $status)
    {
        $this->attendance[$studentId]['status'] = $status;
        $this->saveAttendance($studentId);
    }

    public function markAllWithStatus($status)
    {
        foreach ($this->students as $student) {
            $this->attendance[$student->id]['status'] = $status;
            $this->saveAttendance($student->id);
        }

        $this->loadStats();
    }

    public function saveAttendance($studentId)
    {
        $data = $this->attendance[$studentId];
        $existingRecord = Attendance::where('team_id', $this->team->id)
            ->where('student_id', $studentId)
            ->whereDate('date', $this->date)
            ->first();

        $timeIn = $data['time_in'] ? Carbon::parse($this->date . ' ' . $data['time_in']) : ($data['status'] === 'present' ? now() : null);
        $timeOut = $data['time_out'] ? Carbon::parse($this->date . ' ' . $data['time_out']) : null;

        if ($existingRecord) {
            $existingRecord->update([
                'status' => $data['status'],
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'notes' => $data['notes'],
            ]);
        } else {
            $newRecord = Attendance::create([
                'team_id' => $this->team->id,
                'student_id' => $studentId,
                'created_by' => Auth::id(),
                'status' => $data['status'],
                'date' => $this->date,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'notes' => $data['notes'],
            ]);

            $this->attendance[$studentId]['id'] = $newRecord->id;
        }

        $this->loadStats();
    }

    public function markTimeOut($studentId)
    {
        $this->attendance[$studentId]['time_out'] = now()->format('H:i');
        $this->saveAttendance($studentId);
    }

    public function toggleShowQrCode()
    {
        $this->showQrCode = !$this->showQrCode;
    }

    public function generateQrCode()
    {
        if ($this->activeQrCode) {
            $this->activeQrCode->deactivate();
        }

        $this->activeQrCode = AttendanceQrCode::createForTeam(
            $this->team,
            Auth::user(),
            Carbon::parse($this->date),
            $this->qrCodeExpiryMinutes,
            $this->qrCodeDescription ?: 'Attendance for ' . $this->date
        );

        $this->showQrCode = true;
    }

    public function extendQrCodeExpiry($minutes)
    {
        if ($this->activeQrCode) {
            $this->activeQrCode->extendExpiry($minutes);
            $this->activeQrCode->refresh();
        }
    }

    public function deactivateQrCode()
    {
        if ($this->activeQrCode) {
            $this->activeQrCode->deactivate();
            $this->activeQrCode = null;
        }
    }

    public function resetForm()
    {
        $this->reset(['notes', 'selectedStatus']);
    }

    public function render()
    {
        return view('livewire.attendance-manager');
    }
}
