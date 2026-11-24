<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Course;
use App\Models\User;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'is_active',
    ];

    /**
     * العلاقة بين Student و User
     * كل Student تابع لـ User واحد
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة بين Student و Course
     * الطالب يمكن أن يسجل في أكثر من Course
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'enrollments');
    }
}