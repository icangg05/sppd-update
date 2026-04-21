<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\DepartmentType;
use App\Enums\SppdDomain;

class SppdWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department_type',
        'applicant_role',
        'destination',
        'steps',
        'is_active',
    ];

    protected $casts = [
        'steps' => 'array',
        'department_type' => DepartmentType::class,
        'destination' => SppdDomain::class,
        'is_active' => 'boolean',
    ];
}
