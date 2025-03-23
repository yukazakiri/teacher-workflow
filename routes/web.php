<?php

declare(strict_types=1);

use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivitySubmissionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', fn () => view('welcome'))->name('welcome');

Route::redirect('/login', '/app/login')->name('login');

Route::redirect('/register', '/app/register')->name('register');

Route::redirect('/dashboard', '/app')->name('dashboard');

Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
    ->middleware(['signed', 'verified', 'auth', AuthenticateSession::class])
    ->name('team-invitations.accept');

Route::delete('/team-invitations/{invitation}', [TeamInvitationController::class, 'destroy'])
    ->middleware(['auth', AuthenticateSession::class])
    ->name('team-invitations.destroy');

// Exam routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/exams/{exam}/export', [ExamController::class, 'export'])->name('exams.export');
    Route::post('/exams/export-bulk', [ExamController::class, 'exportBulk'])->name('exams.export-bulk');
});

// Activity routes
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    // Activity progress and reporting
    Route::get('/activities/{activity}/progress', function (App\Models\Activity $activity) {
        return view('activities.progress', ['activity' => $activity]);
    })->name('activities.progress');

    Route::get('/activities/{activity}/generate-report', [ActivityController::class, 'generateReport'])->name('activities.generate-report');

    // Submission management
    Route::post('/activity-submissions/{submission}/grade', [ActivityController::class, 'gradeSubmission'])->name('activities.grade-submission');
    Route::get('/activity-submissions/{submission}', [ActivityController::class, 'viewSubmission'])->name('activities.view-submission');

    // Group management
    Route::post('/activities/{activity}/groups', [ActivityController::class, 'createGroup'])->name('activities.create-group');
    Route::post('/groups/{group}/add-student', [ActivityController::class, 'addStudentToGroup'])->name('groups.add-student');
    Route::delete('/groups/{group}/remove-student', [ActivityController::class, 'removeStudentFromGroup'])->name('groups.remove-student');

    // Role management
    Route::post('/activities/{activity}/roles', [ActivityController::class, 'createRole'])->name('activities.create-role');
    Route::post('/role-assignments', [ActivityController::class, 'assignRole'])->name('role-assignments.assign');
    Route::delete('/role-assignments/{assignment}', [ActivityController::class, 'removeRoleAssignment'])->name('role-assignments.remove');

    Route::get('/activities/{activity}/submit', [ActivitySubmissionController::class, 'showSubmissionForm'])
        ->name('activities.submit');
    
    Route::post('/activities/{activity}/submit', [ActivitySubmissionController::class, 'storeSubmission'])
        ->name('activities.submit.store');
    
    Route::delete('/submissions/{submission}/attachments/{index}', [ActivitySubmissionController::class, 'deleteAttachment'])
        ->name('submissions.attachments.delete');
});
