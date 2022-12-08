<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Employees\JobTitles;
use App\Http\Controllers\Employees\Shedules;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeWorkDate;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Employees extends Controller
{
    /**
     * Вывод сотрудников
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $rows = Employee::lazy()
            ->map(function ($row) {
                return $this->employee($row);
            })
            ->sortBy([
                ["job_title", "asc"],
                ["fullname", "asc"],
            ])
            ->values();

        return response()->json([
            'rows' => $rows ?? [],
        ]);
    }

    /**
     * Формирует строку сотрудника
     * 
     * @param  \App\Models\Employee $row
     * @return \App\Models\Employee
     */
    public static function employee(Employee $row)
    {
        $row->fullname = $row->fullname;
        $row->work_shedule = $row->work_shedule;
        $row->work_shedule_time = $row->work_shedule_time;

        $work_dates = EmployeeWorkDate::whereEmployeeId($row->id)->orderBy('id', "DESC")->first();
        $row->date_work_start = $work_dates->work_start ?? null;
        $row->date_work_stop = $work_dates->work_stop ?? null;

        $salary = EmployeeSalary::whereEmployeeId($row->id)->orderBy('start_date', "DESC")->first();
        $row->salary = $salary->salary ?? 0;
        $row->salary_one_day = $salary->is_one_day ?? false;
        $row->salary_date = $salary
            ? now()->create($salary->start_date)->format("Y-m-d")
            : $row->date_work_start;

        if (is_array($row->personal_data)) {
            foreach ($row->personal_data as $key => $value) {
                $row->{"personal_data_" . $key} = $value;
            }
        }

        return $row;
    }

    /**
     * Создание нового сотрудника
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $request->validate([
            'surname' => "required",
            'name' => "required",
            'phone' => "required",
        ]);

        $row = $this->createEmployeeRow($request);

        return response()->json(
            $this->employee($row)
        );
    }

    /**
     * Создание строки сотрудника
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \App\Models\Employee
     */
    public static function createEmployeeRow(Request $request)
    {
        $row = new Employee;

        $row->pin = self::findNewPin();
        $row->employee_otdel_id = $request->employee_otdel_id;
        $row->surname = $request->surname;
        $row->name = $request->name;
        $row->middle_name = $request->middle_name;
        $row->job_title = $request->job_title;
        $row->phone = $request->phone;
        $row->telegram_id = $request->telegram_id;
        $row->email = $request->email;
        $row->personal_data = $request->personal_data;

        $row->save();

        Log::write($row, $request);

        EmployeeWorkDate::create([
            'employee_id' => $row->id,
            'work_start' => now(),
        ]);

        return $row;
    }

    /**
     * Изменение данных сотруднкиа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        if (!$row = Employee::find($request->id))
            return response()->json(['message' => "Данные сотрудника не найдены"], 400);

        $personal_data = [];

        foreach ($request->all() as $key => $value) {
            if (Str::startsWith($key, "personal_data_")) {
                $personal_data[Str::replace("personal_data_", "", $key)] = $value;
            }
        }

        $row->personal_data = $personal_data;

        $row->employee_otdel_id = $request->employee_otdel_id;
        $row->surname = $request->surname;
        $row->name = $request->name;
        $row->middle_name = $request->middle_name;
        $row->job_title = $request->job_title;
        $row->phone = $request->phone;
        $row->telegram_id = $request->telegram_id;
        $row->email = $request->email;

        $row->save();

        Log::write($row, $request);

        EmployeeWorkDate::checkAndChangeWorkDate(
            $row->id,
            $request->date_work_start,
            $request->date_work_stop
        );

        return response()->json(
            $this->employee($row),
        );
    }

    /**
     * Находит персональный идентификационный номер для нового сотрудника
     * 
     * @return int
     */
    public static function findNewPin()
    {
        $max = (int) Employee::withTrashed()->max('pin');

        return ($max > 100 ? $max : 100) + 1;
    }

    /**
     * Выводит данные сотрудника
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if (!$row = Employee::find($request->id))
            return response()->json(['message' => "Данные сотрудника не найдены"], 400);

        return response()->json([
            'row' => $this->employee($row),
            'shedule' => (new Shedules)->getSheduleTypes(),
            'jobTitles' => JobTitles::list($row->employee_otdel_id),
        ]);
    }
}
