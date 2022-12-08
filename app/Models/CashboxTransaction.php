<?php

namespace App\Models;

use App\Jobs\UpdateEmployeeDutyJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashboxTransaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Атрибуты, которые необходимо преобразовать
     * 
     * @var array
     */
    protected $casts = [
        'is_income' => "boolean",
        'is_expense' => "boolean",
    ];

    // /**
    //  * Метод «booted» модели.
    //  *
    //  * @return void
    //  */
    // protected static function booted()
    // {
    //     parent::booted();

    //     static::created(function ($row) {
    //         if ($row->expense_type_id == 1)
    //             static::startUpdateEmployeeDutyJob($row->expense_subtype_id, $row->date, abs($row->sum));
    //     });

    //     static::updated(function ($row) {
    //         if ($row->expense_type_id == 1) {

    //             $original = $row->getOriginal();
    //             $changes = $row->getChanges();

    //             if ($changes['sum'] ?? null)
    //                 static::startUpdateEmployeeDutyJob(
    //                     $row->expense_subtype_id,
    //                     $row->date,
    //                     abs($row->sum),
    //                     $original['date'] ?? null,
    //                     ($original['sum'] ?? null) ? abs($original['sum']) : null,
    //                 );
    //         }
    //     });
    // }

    // /**
    //  * Запуск обработки расчет долга
    //  * 
    //  * @param  int  $user_id
    //  * @param  string  $date
    //  * @param  float  $money
    //  * @param  string|null  $update_date
    //  * @param  float|null  $money_update
    //  * @return null
    //  */
    // public static function startUpdateEmployeeDutyJob($user_id, $date, $money, $update_date = null, $money_update = null)
    // {
    //     UpdateEmployeeDutyJob::dispatch($user_id, $date, $money, $update_date, $money_update);
    // }
}
