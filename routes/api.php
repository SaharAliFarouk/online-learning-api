```php
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\CourseController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\AuthController;


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| Public Courses
|--------------------------------------------------------------------------
*/

// Anyone can view courses
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/courses/{id}/students', [CourseController::class, 'students']);
});

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'student'])->group(function () {

    // View students
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{id}', [StudentController::class, 'show']);
    Route::get('/students/{id}/courses', [StudentController::class, 'courses']);

    // Enrollment
    Route::post('/enrollments', [EnrollmentController::class, 'store']);
    Route::get('/enrollments', [EnrollmentController::class, 'index']);
    Route::get('/enrollments/{id}', [EnrollmentController::class, 'show']);

});


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    /*
    | Admin Dashboard
    */

    Route::get('/admin/dashboard', function (Request $request) {
        return response()->json([
            'message' => 'Welcome Admin',
            'user' => $request->user(),
        ]);
    });


    /*
    | Courses Management
    */

    Route::post('/courses', [CourseController::class, 'store']);
    Route::put('/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);


    /*
    | Students Management
    */

    Route::post('/students', [StudentController::class, 'store']);
    Route::put('/students/{id}', [StudentController::class, 'update']);
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);


    /*
    | Enrollment Management
    */

    Route::put('/enrollments/{id}', [EnrollmentController::class, 'update']);
    Route::delete('/enrollments/{id}', [EnrollmentController::class, 'destroy']);

    Route::delete(
        '/students/{student}/courses/{course}',
        [EnrollmentController::class, 'detach']
    );

});

