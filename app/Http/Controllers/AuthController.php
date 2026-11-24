<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;
use App\Http\Resources\StudentResource;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register
    |--------------------------------------------------------------------------
    |
    | إنشاء حساب Student جديد.
    |
    | عند التسجيل:
    | 1. يتم إنشاء User.
    | 2. يتم تحديد role = student تلقائيًا.
    | 3. يتم إنشاء Student Profile.
    | 4. يتم ربط Student بالـ User باستخدام user_id.
    | 5. يتم إنشاء API Token.
    |
    | DB::transaction() تضمن أن كل العمليات تنجح معًا،
    | وإذا حدث خطأ يتم عمل Rollback.
    |
    */

    public function register(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Database Transaction
        |--------------------------------------------------------------------------
        */

        $result = DB::transaction(function () use ($request) {

            /*
            |--------------------------------------------------------------------------
            | Create User
            |--------------------------------------------------------------------------
            |
            | role = student يتم تحديدها من السيرفر،
            | ولا نسمح للمستخدم بإرسال role بنفسه.
            |
            */

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'role' => 'student',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Create Student Profile
            |--------------------------------------------------------------------------
            |
            | نربط Student بالـ User من خلال user_id.
            |
            */

            $student = Student::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'is_active' => true,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Create API Token
            |--------------------------------------------------------------------------
            */

            $token = $user->createToken('api-token')->plainTextToken;


            return [
                'user' => $user,
                'student' => $student,
                'token' => $token,
            ];
        });


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Student registered successfully',
            'user' => new UserResource($result['user']),
            'student' => new StudentResource($result['student']),
            'token' => $result['token'],
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    |
    | تسجيل الدخول باستخدام Email + Password.
    |
    */

    public function login(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $user = User::where('email', $request->email)->first();


        /*
        |--------------------------------------------------------------------------
        | Check Credentials
        |--------------------------------------------------------------------------
        */

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password'
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | Delete Old Tokens
        |--------------------------------------------------------------------------
        |
        | نحذف الـ Tokens القديمة لهذا المستخدم
        | قبل إنشاء Token جديد.
        |
        | هذا يمنع تراكم عدد كبير من Tokens القديمة.
        |
        */

        $user->tokens()->delete();


        /*
        |--------------------------------------------------------------------------
        | Create New Token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken('api-token')->plainTextToken;


        /*
        |--------------------------------------------------------------------------
        | Get Student Profile
        |--------------------------------------------------------------------------
        |
        | لو المستخدم Student، نحصل على الـ Student المرتبط به.
        |
        | لو Admin، ستكون النتيجة null.
        |
        */

        $student = $user->student;
        


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'student' => $student ? new StudentResource($student) : null,
            'token' => $token,
        ]);
    }
}