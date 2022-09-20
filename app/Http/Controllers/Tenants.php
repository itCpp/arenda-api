<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Incomes\Files;
use App\Http\Controllers\Incomes\Purposes;
use App\Http\Controllers\Incomes\Sources;
use App\Models\IncomeSource;
use Illuminate\Http\Request;

class Tenants extends Controller
{
    /**
     * Основные данные арендатора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if (!$row = IncomeSource::find($request->id))
            return response()->json(['message' => "Данные по арендатору не найдены"], 400);

        $request->merge([
            'income' => true,
            // 'toLastMonth' => true,
        ]);

        $row = (new Sources)->getIncomeSourceRow($row);

        $pays = (new Incomes)->view($request, $row);

        $files = (new Files($request))->getFilesList($request);

        return response()->json([
            'row' => $row,
            'pays' => $pays,
            'files' => $files,
            'purposes' => Purposes::getAll(),
        ]);
    }
}