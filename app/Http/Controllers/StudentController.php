<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Student Profile
    |--------------------------------------------------------------------------
    |
    | عرض بيانات الطالب الحالي فقط.
    |
    | الـ Route محمي بـ auth:sanctum + student
    | لذلك الطالب لا يستطيع الوصول إلى بيانات طالب آخر.
    |
    */

    public function index(Request $request)
    {
        // الحصول على Student المرتبط بالـ User الذي سجل الدخول
        $student = $request->user()->student;

        // التأكد أن الـ User لديه Student Profile
        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.'
            ], 404);
        }

        return new StudentResource($student);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Student
    |--------------------------------------------------------------------------
    |
    | إنشاء Student جديد.
    |
    | هذه العملية خاصة بالـ Admin حسب الـ Route.
    |
    */

    public function store(StoreStudentRequest $request)
    {
        $student = Student::create($request->validated());

        return response()->json([
            'message' => 'Student created successfully',
            'data' => new StudentResource($student)
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Student
    |--------------------------------------------------------------------------
    |
    | الطالب يستطيع رؤية بياناته فقط.
    |
    | مثال:
    | Mona ID = 3
    |
    | GET /students/3  → مسموح
    | GET /students/5  → ممنوع
    |
    */

    public function show(Request $request, $id)
    {
        // الحصول على الطالب المرتبط بالمستخدم الحالي
        $student = $request->user()->student;

        // منع الطالب من الوصول إلى Student آخر
        if (!$student || $student->id != $id) {
            return response()->json([
                'message' => 'Forbidden. You can only view your own student profile.'
            ], 403);
        }

        // تحميل الكورسات الخاصة بالطالب
        $student->load('courses');

        return new StudentResource($student);
    }


    /*
    |--------------------------------------------------------------------------
    | Student Courses
    |--------------------------------------------------------------------------
    |
    | الطالب يستطيع مشاهدة الكورسات الخاصة به فقط.
    |
    */

    public function courses(Request $request, $id)
    {
        // الحصول على الطالب الحالي
        $student = $request->user()->student;

        // منع الوصول إلى كورسات طالب آخر
        if (!$student || $student->id != $id) {
            return response()->json([
                'message' => 'Forbidden. You can only view your own courses.'
            ], 403);
        }

        return response()->json([
            'data' => $student->courses
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Student
    |--------------------------------------------------------------------------
    |
    | تعديل بيانات Student.
    |
    | الـ Route موجود داخل Admin Middleware،
    | لذلك Admin فقط يستطيع تنفيذ العملية.
    |
    */

    public function update(UpdateStudentRequest $request, $id)
    {
        $student = Student::findOrFail($id);

        $student->update($request->validated());

        return response()->json([
            'message' => 'Student updated successfully',
            'data' => new StudentResource($student)
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Student
    |--------------------------------------------------------------------------
    |
    | حذف Student.
    |
    | الـ Route موجود داخل Admin Middleware،
    | لذلك Admin فقط يستطيع الحذف.
    |
    */

    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        $student->delete();

        return response()->json([
            'message' => 'Student deleted successfully'
        ]);
    }
}