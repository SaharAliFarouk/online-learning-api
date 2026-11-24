<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Models\Student;
use App\Http\Resources\EnrollmentResource;
use App\Http\Requests\UpdateEnrollmentRequest;
use Illuminate\Database\QueryException;

class EnrollmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Create Enrollment
    |--------------------------------------------------------------------------
    |
    | الطالب يسجل في Course.
    | مهم: لا نعتمد على student_id القادم من المستخدم،
    | لأن الطالب ممكن يرسل ID لطالب آخر.
    |
    */
public function store(StoreEnrollmentRequest $request)
{
    /*
    |--------------------------------------------------------------------------
    | Get the logged-in student's profile
    |--------------------------------------------------------------------------
    */

    $student = $request->user()->student;

    if (!$student) {
        return response()->json([
            'message' => 'Student profile not found.'
        ], 404);
    }

    /*
    |--------------------------------------------------------------------------
    | Get validated data
    |--------------------------------------------------------------------------
    */

    $data = $request->validated();

    /*
    |--------------------------------------------------------------------------
    | Set student_id automatically
    |--------------------------------------------------------------------------
    */

    $data['student_id'] = $student->id;

    /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Enrollment
    |--------------------------------------------------------------------------
    */

    $alreadyEnrolled = Enrollment::where('student_id', $student->id)
        ->where('course_id', $data['course_id'])
        ->exists();

    if ($alreadyEnrolled) {
        return response()->json([
            'message' => 'You are already enrolled in this course.'
        ], 422);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Enrollment
    |--------------------------------------------------------------------------
    */

    try {

        $enrollment = Enrollment::create($data);

    } catch (QueryException $e) {

        return response()->json([
            'message' => 'Unable to create enrollment.'
        ], 500);
    }

    /*
    |--------------------------------------------------------------------------
    | Load relationships
    |--------------------------------------------------------------------------
    */

    $enrollment->load(['student', 'course']);

    return response()->json([
        'message' => 'Student enrolled successfully',
        'data' => new EnrollmentResource($enrollment)
    ], 201);
}

    /*
    |--------------------------------------------------------------------------
    | Show One Enrollment
    |--------------------------------------------------------------------------
    */

   public function show(Request $request, $id)
{
    // الحصول على الطالب المرتبط بالـ User الذي سجل الدخول
    $student = $request->user()->student;

    // التأكد أن لديه Student Profile
    if (!$student) {
        return response()->json([
            'message' => 'Student profile not found.'
        ], 404);
    }

    // البحث عن Enrollment الخاص بهذا الطالب فقط
    $enrollment = Enrollment::with(['student', 'course'])
        ->where('id', $id)
        ->where('student_id', $student->id)
        ->first();

    // إذا كان الـ Enrollment غير موجود أو يخص طالبًا آخر
    if (!$enrollment) {
        return response()->json([
            'message' => 'Enrollment not found or access denied.'
        ], 404);
    }

    return new EnrollmentResource($enrollment);
}

    /*
    |--------------------------------------------------------------------------
    | Update Enrollment
    |--------------------------------------------------------------------------
    */

    public function update(UpdateEnrollmentRequest $request, $id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $enrollment->update($request->validated());

        $enrollment->load(['student', 'course']);

        return response()->json([
            'message' => 'Enrollment updated successfully',
            'data' => new EnrollmentResource($enrollment)
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Enrollment
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $enrollment->delete();

        return response()->json([
            'message' => 'Enrollment deleted successfully'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Detach Course from Student
    |--------------------------------------------------------------------------
    |
    | إزالة الكورس من الطالب.
    |
    | هذه العملية موجودة حاليًا في Admin Routes،
    | لذلك سنراجع صلاحياتها لاحقًا.
    |
    */

    public function detach($student, $course)
    {
        $student = Student::findOrFail($student);

        $student->courses()->detach($course);

        return response()->json([
            'message' => 'Course detached successfully'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Attach Course
    |--------------------------------------------------------------------------
    |
    | الكود ده كان تجربة قديمة باستخدام attach().
    | مش محتاجينه حاليًا لأننا بنستخدم Enrollment::create().
    |
    */

    /*
    public function attach($student, $course)
    {
        $student = Student::findOrFail($student);

        $student->courses()->attach($course);

        return response()->json([
            'message' => 'Course attached successfully'
        ]);
    }
    */


    /*
    |--------------------------------------------------------------------------
    | Sync Courses
    |--------------------------------------------------------------------------
    |
    | تجربة قديمة باستخدام sync().
    |
    */

    /*
    public function sync()
    {
        $student = Student::findOrFail(1);

        $student->courses()->sync([1, 2]);

        return response()->json([
            'message' => 'Courses synced successfully'
        ]);
    }
    */
}