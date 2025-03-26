<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceQrCode;
use App\Models\Student;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    /**
     * Mark attendance for a student.
     */
    public function markAttendance(Request $request, Team $team)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'status' => 'required|in:present,absent,late,excused',
            'date' => 'required|date',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        // Check if attendance already exists for this student on this date
        $existingAttendance = Attendance::where('team_id', $team->id)
            ->where('student_id', $request->student_id)
            ->whereDate('date', $request->date)
            ->first();

        if ($existingAttendance) {
            // Update existing attendance
            $existingAttendance->update([
                'status' => $request->status,
                'time_in' => $request->time_in ? $request->date . ' ' . $request->time_in : null,
                'time_out' => $request->time_out ? $request->date . ' ' . $request->time_out : null,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => 'Attendance updated successfully',
                'attendance' => $existingAttendance,
            ]);
        }

        // Create new attendance record
        $attendance = Attendance::create([
            'team_id' => $team->id,
            'student_id' => $request->student_id,
            'created_by' => Auth::id(),
            'status' => $request->status,
            'date' => $request->date,
            'time_in' => $request->time_in ? $request->date . ' ' . $request->time_in : null,
            'time_out' => $request->time_out ? $request->date . ' ' . $request->time_out : null,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Attendance marked successfully',
            'attendance' => $attendance,
        ]);
    }

    /**
     * Scan QR code to mark attendance.
     */
    public function scanQr(Request $request, string $code)
    {
        // Find the QR code
        $qrCode = AttendanceQrCode::where('code', $code)->first();

        if (!$qrCode) {
            throw ValidationException::withMessages([
                'code' => ['Invalid QR code'],
            ]);
        }

        // Check if QR code is active and not expired
        if (!$qrCode->isValid()) {
            throw ValidationException::withMessages([
                'code' => ['QR code has expired or is inactive'],
            ]);
        }

        // Get the authenticated user's student record in the team
        $student = Student::where('user_id', Auth::id())
            ->where('team_id', $qrCode->team_id)
            ->first();

        if (!$student) {
            throw ValidationException::withMessages([
                'student' => ['You are not a member of this class'],
            ]);
        }

        // Check if attendance already exists for this student on this date
        $existingAttendance = Attendance::where('team_id', $qrCode->team_id)
            ->where('student_id', $student->id)
            ->whereDate('date', $qrCode->date)
            ->first();

        if ($existingAttendance) {
            // Update existing attendance
            $existingAttendance->update([
                'status' => 'present',
                'time_in' => now(),
                'qr_verified' => true,
            ]);

            return response()->json([
                'message' => 'Attendance updated successfully',
                'attendance' => $existingAttendance,
            ]);
        }

        // Create new attendance record
        $attendance = Attendance::create([
            'team_id' => $qrCode->team_id,
            'student_id' => $student->id,
            'created_by' => Auth::id(),
            'status' => 'present',
            'date' => $qrCode->date,
            'time_in' => now(),
            'qr_verified' => true,
        ]);

        return response()->json([
            'message' => 'Attendance marked successfully',
            'attendance' => $attendance,
        ]);
    }

    /**
     * Mark time out for a student.
     */
    public function markTimeOut(Request $request, Team $team, Student $student)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        // Find the latest attendance record for this student
        $attendance = Attendance::where('team_id', $team->id)
            ->where('student_id', $student->id)
            ->whereDate('date', $request->date)
            ->first();

        if (!$attendance) {
            throw ValidationException::withMessages([
                'attendance' => ['No attendance record found for this date'],
            ]);
        }

        // Update time out
        $attendance->update([
            'time_out' => now(),
        ]);

        return response()->json([
            'message' => 'Time out marked successfully',
            'attendance' => $attendance,
        ]);
    }

    /**
     * Show QR code scanning page.
     */
    public function showScanPage(string $code)
    {
        return view('attendance.scan', [
            'code' => $code,
        ]);
    }

    /**
     * Get attendance statistics for a team.
     */
    public function getTeamStats(Team $team, $date = null)
    {
        $date = $date ?? now()->toDateString();

        $totalStudents = $team->students()->count();
        $presentCount = Attendance::where('team_id', $team->id)
            ->whereDate('date', $date)
            ->where('status', 'present')
            ->count();
        $absentCount = Attendance::where('team_id', $team->id)
            ->whereDate('date', $date)
            ->where('status', 'absent')
            ->count();
        $lateCount = Attendance::where('team_id', $team->id)
            ->whereDate('date', $date)
            ->where('status', 'late')
            ->count();
        $excusedCount = Attendance::where('team_id', $team->id)
            ->whereDate('date', $date)
            ->where('status', 'excused')
            ->count();

        // Calculate unrecorded students
        $unrecordedCount = $totalStudents - $presentCount - $absentCount - $lateCount - $excusedCount;

        return response()->json([
            'total_students' => $totalStudents,
            'present' => $presentCount,
            'absent' => $absentCount,
            'late' => $lateCount,
            'excused' => $excusedCount,
            'unrecorded' => $unrecordedCount,
            'date' => $date,
        ]);
    }
}
