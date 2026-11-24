<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;

class CourseController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get All Courses
    |--------------------------------------------------------------------------
    |
    | عرض جميع الكورسات.
    |
    | هذا الـ endpoint Public حاليًا،
    | لذلك لا يحتاج إلى تسجيل دخول.
    |
    */

  public function index(Request $request)
{
    // إنشاء Query
    $query = Course::query();

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    | مثال:
    | ?search=Laravel
    */
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Filter
    |--------------------------------------------------------------------------
    | مثال:
    | ?is_active=1
    */
    if ($request->has('is_active')) {
        $query->where('is_active', $request->is_active);
    }

    /*
    |--------------------------------------------------------------------------
    | Sorting
    |--------------------------------------------------------------------------
    */

    // الأعمدة المسموح للـ Frontend يستخدمها في الترتيب
    $allowedSorts = [
        'title',
        'price',
        'created_at',
    ];

    // لو Frontend لم يرسل sort نستخدم created_at
    $sort = $request->get('sort', 'created_at');

    // التأكد أن العمود مسموح
    if (!in_array($sort, $allowedSorts)) {
        $sort = 'created_at';
    }

    // الاتجاه الافتراضي ascending
    $direction = $request->get('direction', 'asc');

    // السماح فقط بـ asc أو desc
    if (!in_array($direction, ['asc', 'desc'])) {
        $direction = 'asc';
    }

    $query->orderBy($sort, $direction);

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    $courses = $query->paginate(10);

    return CourseResource::collection($courses);
}


    /*
    |--------------------------------------------------------------------------
    | Show One Course
    |--------------------------------------------------------------------------
    |
    | عرض بيانات Course واحد.
    |
    | هذا الـ endpoint Public حاليًا.
    |
    */

    public function show($id)
    {
        $course = Course::findOrFail($id);

        return new CourseResource($course);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Course
    |--------------------------------------------------------------------------
    |
    | إنشاء Course جديد.
    |
    | الـ Route موجود داخل Admin Middleware،
    | لذلك Admin فقط يستطيع إنشاء Course.
    |
    */

    public function store(StoreCourseRequest $request)
    {
        $course = Course::create($request->validated());

        return response()->json([
            'message' => 'Course created successfully',
            'data' => new CourseResource($course)
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Get Students of a Course
    |--------------------------------------------------------------------------
    |
    | عرض الطلاب المسجلين في Course معين.
    |
    | هذا الـ endpoint كان Public،
    | لكننا سنجعله متاحًا للطلاب المسجلين فقط.
    |
    */

    public function students(Request $request, $id)
    {
        /*
        |--------------------------------------------------------------------------
        | Get Logged-in Student
        |--------------------------------------------------------------------------
        |
        | نحدد الطالب من الـ User الذي سجل الدخول
        | باستخدام الـ Sanctum Token.
        |
        */

        $student = $request->user()->student;

        // التأكد أن الـ User لديه Student Profile
        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.'
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Find Course
        |--------------------------------------------------------------------------
        */

        $course = Course::findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | Check Enrollment
        |--------------------------------------------------------------------------
        |
        | نتأكد أن الطالب الحالي مسجل في هذا الكورس.
        |
        */

        $isEnrolled = $course->students()
            ->where('students.id', $student->id)
            ->exists();


        if (!$isEnrolled) {
            return response()->json([
                'message' => 'Forbidden. You are not enrolled in this course.'
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | Return Students
        |--------------------------------------------------------------------------
        |
        | بعد التأكد من صلاحية الطالب،
        | نعرض الطلاب المسجلين في الكورس.
        |
        */

        return response()->json([
            'data' => $course->students
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Course
    |--------------------------------------------------------------------------
    |
    | تعديل Course.
    |
    | الـ Route موجود داخل Admin Middleware،
    | لذلك Admin فقط يستطيع التعديل.
    |
    */

    public function update(UpdateCourseRequest $request, $id)
    {
        $course = Course::findOrFail($id);

        $course->update($request->validated());

        return response()->json([
            'message' => 'Course updated successfully',
            'data' => new CourseResource($course)
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Course
    |--------------------------------------------------------------------------
    |
    | حذف Course.
    |
    | الـ Route موجود داخل Admin Middleware،
    | لذلك Admin فقط يستطيع الحذف.
    |
    */

    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully'
        ]);
    }
}