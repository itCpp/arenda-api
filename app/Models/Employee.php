<?php

namespace App\Models;

use App\Http\Controllers\Employees\Shedules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pin',
        'employee_otdel_id',
        'surname',
        'name',
        'middle_name',
        'job_title',
        'phone',
        'telegram_id',
        'email',
        'hidden',
        'personal_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hidden' => 'boolean',
        'personal_data' => 'array',
    ];

    /**
     * Получить полное имя пользователя.
     *
     * @return string
     */
    public function getFullnameAttribute()
    {
        return trim("{$this->surname} {$this->name} {$this->middle_name}");
    }

    /**
     * Получить график работы.
     *
     * @return string|null
     */
    public function getWorkSheduleAttribute()
    {
        return (new Shedules)->getType($this->personal_data['work_shedule'] ?? null);
    }

    /**
     * Получить время работы.
     *
     * @return string|null
     */
    public function getWorkSheduleTimeAttribute()
    {
        $time = "";

        if ($this->personal_data['work_shedule_time_with'] ?? null)
            $time .= "c " . $this->personal_data['work_shedule_time_with'];

        if ($this->personal_data['work_shedule_time_on'] ?? null)
            $time .= " до " . $this->personal_data['work_shedule_time_on'] . " ";

        $time = trim($time);

        return (bool) $time ? $time : null;
    }
}
