<?php

namespace App\Http\Controllers\Tenants;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Incomes;
use App\Models\AdditionalService;
use App\Models\IncomeSource;
use App\Models\Log;
use Illuminate\Http\Request;

class AdditionalServices extends Controller
{
    /**
     * Выводит список дополнительных услуг арендатора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$row = IncomeSource::find($request->id))
            return response()->json(['message' => "Арендатор не найден"], 400);

        return response()->json([
            'rows' => $this->getServices($row->services),
        ]);
    }

    /**
     * Формирует строку услуги
     * 
     * @param  \Illuminate\Database\Eloquent\Collection<\App\Models\AdditionalService>
     * @return array
     */
    public function getServices($rows)
    {
        return $rows->map(function ($row) {
            return array_merge(
                optional($row->pivot)->toArray(),
                $row->toArray(),
            );
        })->toArray();
    }

    /**
     * Список дополнительных услуг
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request, $is_one = false, $to_array = false)
    {
        $rows = AdditionalService::whereIsOne($is_one)
            ->get()
            ->map(function ($row) {
                return [
                    'value' => $row->id,
                    'text' => $row->name,
                ];
            });

        if ($to_array)
            return $rows->toArray();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * Создает строку сервиса
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  null|string $data
     * @return \App\Models\AdditionalService
     */
    public function createServise($request, $name = null)
    {
        $row = AdditionalService::create([
            'name' => $name ?: $request->name,
            'icon' => $request->icon,
        ]);

        Log::write($row, $request);

        return $row;
    }

    /**
     * Сохраняет услугу
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $request->validate([
            'additional_service_id' => "required",
            'sum' => "required|numeric",
            'start_date' => "required|date",
            'type_pay' => "required|numeric",
        ]);

        if (!$row = IncomeSource::find($request->id))
            return response()->json(['message' => "Арендатор не найден"], 400);

        if (is_string($request->additional_service_id)) {
            $request->additional_service_id = $this->createServise($request, $request->additional_service_id)->id ?? null;
        }

        $data = [
            'sum' => $request->sum,
            'type_pay' => $request->type_pay,
            'start_date' => $request->start_date,
            'updated_at' => now()->format("Y-m-d H:i:s"),
        ];

        if ($row->services()->where('additional_service_id', $request->additional_service_id)->count()) {
            $row->services()->updateExistingPivot($request->additional_service_id, $data);
        } else {
            $row->services()->attach(
                $request->additional_service_id,
                array_merge(
                    $data,
                    ['created_at' => now()->format("Y-m-d H:i:s")]
                )
            );
        }

        Log::write($row, $request);

        return response()->json([
            'row' => $row,
            'list' => $this->getServices($row->services),
            'pays' => $request->toPays ? (new Incomes)->view($request, $row) : null,
        ]);
    }

    /**
     * Удаляет услугу
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drop(Request $request)
    {
        if (!$row = IncomeSource::find($request->source_id))
            return response()->json(['message' => "Арендатор не найден"], 400);

        if ($row->services()->where('additional_service_id', $request->id)->count()) {
            $row->services()->updateExistingPivot($request->id, ['deleted_at' => now()]);
        }

        Log::write($row, $request);

        return response()->json([
            'row' => $row,
            'list' => $this->getServices($row->services),
        ]);
    }
}
