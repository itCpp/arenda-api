<?php

namespace App\Models;

use App\Casts\Date;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeWorkDate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'work_start',
        'work_stop',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'work_start' => Date::class,
        'work_stop' => Date::class,
    ];

    /**
     * Проверяет и изменяет периода работы
     * 
     * @param  int $user_id
     * @param  null|string $start
     * @param  null|string $stop
     * @return \App\Models\EmployeeWorkDate
     */
    public static function checkAndChangeWorkDate($user_id, $start = null, $stop = null)
    {
        $row = static::whereEmployeeId($row->id)->orderBy('id', "DESC")->first();

        if (!$row) {
            return static::create([
                'employee_id' => $user_id,
                'work_start' => $start,
                'work_stop' => $stop,
            ]);
        }

        // if ((bool) $row->work_start and !(bool) $row->work_stop) {
        //     $row->
        // }

        return $row;
    }
}
