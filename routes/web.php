<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivitySubmissionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ExamController;
use App\Livewire\TeamAttendance;
use App\Providers\Filament\AppPanelProvider;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session; // Keep this line, even if unused for now, as it might be used elsewhere or intended for future use.
use Laravel\Jetstream\Http\Controllers\TeamInvitationController;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest as RequestsAuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest as RequestsAuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest as RequestsAuthKitLogoutRequest; // Import the AppPanelProvider if needed for URL generation, though direct path is often fine.
use App\Http\Controllers\AiStreamController;
use App\Models\Conversation;
use App\Services\PrismChatService;
use Illuminate\Http\Request;
use App\Http\Controllers\ProfileController;
use App\Livewire\ChatPage;

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

// Determine the appropriate session authentication middleware
$authSessionMiddleware = config('app.use_workos', false)
    ? ValidateSessionWithWorkOS::class
    : AuthenticateSession::class; // Or config('jetstream.auth_session') if Jetstream's default is preferred

// Route::get("/", fn() => redirect("/login"))->name("welcome");
Route::get('/', function () {
    if (Auth::check()) {
        // Redirect authenticated users to the Filament App Panel dashboard
        // Assuming the panel ID is 'app' and its path is '/app' based on other routes
        // For a more robust way, use Filament's helper if available and configured:
        // return redirect(AppPanelProvider::getUrl());
        return redirect('/app');
    }

    // Redirect guests to the login page
    return redirect('/login');
})->name('welcome');

if (config('app.use_workos', true)) {
    // WorkOS Authentication Routes
    Route::get('login', function (RequestsAuthKitLoginRequest $request) {
        return $request->redirect();
    })
        ->middleware(['guest'])
        ->name('login');

    Route::get('authenticate', function (
        RequestsAuthKitAuthenticationRequest $request
    ) {
        // Ensure the intended redirect goes to the application's dashboard path
        return tap(
            redirect()->intended('/app'),
            fn () => $request->authenticate()
        );
    })->middleware(['guest']);

    Route::post('logout', function (RequestsAuthKitLogoutRequest $request) {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        // The $request->logout() handles redirecting to WorkOS for logout
        return $request->logout();
    })
        ->middleware(['auth'])
        ->name('logout');

    // Redirect dashboard to the application root under WorkOS context if needed
    Route::redirect('/dashboard', '/app')->name('dashboard');

    // Disable standard registration if using WorkOS for authentication
    // Route::redirect("/register", "/app/register")->name("register"); // Or handle differently
} else {
    // Standard Authentication Routes (Redirects to Jetstream/Filament)
    // Note: The root route '/' now handles the initial redirect based on auth status.
    // These redirects might still be useful for direct access attempts or consistency.
    Route::redirect('/login', '/app/login')->name('login');
    Route::redirect('/register', '/app/register')->name('register');
    Route::redirect('/dashboard', '/app')->name('dashboard');
}

Route::get('/team-invitations/{invitation}', [
    TeamInvitationController::class,
    'accept',
])
    // Use the determined auth session middleware
    ->middleware(['signed', 'verified', 'auth', $authSessionMiddleware])
    ->name('team-invitations.accept');

Route::delete('/team-invitations/{invitation}', [
    TeamInvitationController::class,
    'destroy',
])
    // Use the determined auth session middleware
    ->middleware(['auth', $authSessionMiddleware])
    ->name('team-invitations.destroy');

// Exam routes - Typically require authentication but maybe not the specific session middleware
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/exams/{exam}/export', [ExamController::class, 'export'])->name(
        'exams.export'
    );
    Route::post('/exams/export-bulk', [
        ExamController::class,
        'exportBulk',
    ])->name('exams.export-bulk');
});

// Activity routes
Route::middleware([
    // 'auth:sanctum', // Keep if API token auth is needed alongside web session
    'auth', // Standard web auth guard check for logged-in user
    // $authSessionMiddleware, // REMOVED: Not suitable for these API-like routes
    'verified', // Email verification etc.
])->group(function () {


    // Get all conversations (for API consumers)
    Route::get('/ai/conversations', [AiStreamController::class, 'listConversations']);

    // Get conversation with messages
    Route::get('/ai/conversations/{conversation}/messages', [AiStreamController::class, 'getConversation']);

    // Recent conversations
    Route::get('/chats/recent', function (Request $request) {
        $user = $request->user();
        $currentTeamId = $user->currentTeam?->id;

        if (!$currentTeamId) {
            return response()->json([]);
        }

        return Conversation::where('team_id', $currentTeamId)
            ->where('user_id', $user->id)
            ->orderBy('last_activity_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->title,
                    'model' => $chat->model,
                    'last_activity' => $chat->last_activity_at->diffForHumans(),
                ];
            });
    });


    // Activity progress and reporting
    Route::get('/activities/{activity}/progress', function (
        App\Models\Activity $activity
    ) {
        return view('activities.progress', ['activity' => $activity]);
    })->name('activities.progress');

    Route::get('/activities/{activity}/generate-report', [
        ActivityController::class,
        'generateReport',
    ])->name('activities.generate-report');

    // Submission management
    Route::post('/activity-submissions/{submission}/grade', [
        ActivityController::class,
        'gradeSubmission',
    ])->name('activities.grade-submission');
    Route::get('/activity-submissions/{submission}', [
        ActivityController::class,
        'viewSubmission',
    ])->name('activities.view-submission');

    // Group management
    Route::post('/activities/{activity}/groups', [
        ActivityController::class,
        'createGroup',
    ])->name('activities.create-group');
    Route::post('/groups/{group}/add-student', [
        ActivityController::class,
        'addStudentToGroup',
    ])->name('groups.add-student');
    Route::delete('/groups/{group}/remove-student', [
        ActivityController::class,
        'removeStudentFromGroup',
    ])->name('groups.remove-student');

    // Role management
    Route::post('/activities/{activity}/roles', [
        ActivityController::class,
        'createRole',
    ])->name('activities.create-role');
    Route::post('/role-assignments', [
        ActivityController::class,
        'assignRole',
    ])->name('role-assignments.assign');
    Route::delete('/role-assignments/{assignment}', [
        ActivityController::class,
        'removeRoleAssignment',
    ])->name('role-assignments.remove');

    Route::get('/activities/{activity}/submit', [
        ActivitySubmissionController::class,
        'showSubmissionForm',
    ])->name('activities.submit');

    Route::post('/activities/{activity}/submit', [
        ActivitySubmissionController::class,
        'storeSubmission',
    ])->name('activities.submit.store');

    Route::delete('/submissions/{submission}/attachments/{index}', [
        ActivitySubmissionController::class,
        'deleteAttachment',
    ])->name('submissions.attachments.delete');

    // Get available AI models
    Route::get('/ai/models', [AiStreamController::class, 'getAvailableModels']);

    // Get available chat styles
    Route::get('/ai/styles', [AiStreamController::class, 'getAvailableStyles']);

    // Update conversation model preference
    Route::put('/conversations/{conversation}/model', [AiStreamController::class, 'updateModel']);

    // Update conversation style preference
    Route::put('/conversations/{conversation}/style', [AiStreamController::class, 'updateStyle']);

    // Delete conversation
    Route::delete('/conversations/{conversation}', [AiStreamController::class, 'deleteConversation']);

    // Stream AI response
    Route::post('/ai/stream', [AiStreamController::class, 'streamResponse']);

    // Class resources API route for mention feature
    Route::get('/class-resources/list', function (Request $request) {
        $user = $request->user();
        $team = $user->currentTeam;
        
        if (!$team) {
            return response()->json([]);
        }
        
        $resources = \App\Models\ClassResource::query()
            ->where('team_id', $team->id)
            ->where(fn ($q) => $q->where('is_archived', false)->orWhereNull('is_archived'))
            ->with(['category'])
            ->select(['id', 'title', 'category_id', 'description'])
            ->orderBy('title')
            ->limit(15)
            ->get();
        
        return response()->json($resources);
    });

    // Test student tool directly (for debugging)
    Route::get('/ai/test-student-tool', [AiStreamController::class, 'testStudentTool']);

    // Test Prism library directly (for debugging)
    Route::get('/ai/test-prism', [AiStreamController::class, 'testPrismTools']);

    // Recent conversations - keep the original route for backward compatibility
    Route::get('/chats/recent', [AiStreamController::class, 'listRecentConversations']);
});

// Attendance routes
Route::middleware([
    'auth:sanctum', // API/token auth if applicable
    'auth', // Standard web auth guard check
    $authSessionMiddleware, // Use determined session middleware
    'verified', // Email verification etc.
])->group(function () {
    // Team attendance page - replaced by Filament page
    // Route::get('/team/attendance', TeamAttendance::class)->name('team.attendance');

    // QR code scanning
    Route::get('/attendance/scan/{code}', [
        AttendanceController::class,
        'showScanPage',
    ])->name('attendance.scan');

    Route::post('/attendance/scan/{code}', [
        AttendanceController::class,
        'scanQr',
    ])->name('attendance.scan.process');

    // Attendance management
    Route::post('/teams/{team}/attendance', [
        AttendanceController::class,
        'markAttendance',
    ])->name('attendance.mark');

    Route::post('/teams/{team}/students/{student}/timeout', [
        AttendanceController::class,
        'markTimeOut',
    ])->name('attendance.timeout');

    // Attendance statistics
    Route::get('/teams/{team}/attendance/stats/{date?}', [
        AttendanceController::class,
        'getTeamStats',
    ])->name('attendance.stats');
});

// Add chat API routes with session authentication
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/chat', ChatPage::class);
});
