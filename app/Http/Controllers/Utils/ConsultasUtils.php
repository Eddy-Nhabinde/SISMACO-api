<?php

namespace App\Http\Controllers\Utils;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ConsultasUtils
{
    // function organizeChartDataByEstado($chartData)
    // {
    //     $cancelada = [];
    //     $pendente = [];
    //     $realizada = [];

    //     foreach ($chartData as $key) {
    //         switch ($key->estado) {
    //             case 'Pendente':
    //                 array_push($pendente, $key);
    //                 break;
    //             case 'Cancelada':
    //                 array_push($cancelada, $key);
    //                 break;
    //             case 'Realizada':
    //                 array_push($realizada, $key);
    //                 break;
    //         }
    //     }

    //     return [
    //         "canceladas" => $this->organizeDataByMonth($cancelada),
    //         "pendentes" => $this->organizeDataByMonth($pendente),
    //         "realizadas" => $this->organizeDataByMonth($realizada),
    //     ];
    // }

    function getLast30Days()
    {
        $startDate = Carbon::now()->subDays(30); // Start 30 days ago
        $endDate = Carbon::now(); // Today

        $days = [];
        while ($startDate->lte($endDate)) {
            $days[] = $startDate->day;
            $startDate->addDay();
        }

        return $days;
    }

    function getLast12Months()
    {
        $endDate = Carbon::now(); // Current date
        $startDate = Carbon::now()->subMonths(12); // 12 months ago

        $months = [];
        while ($startDate->lte($endDate)) {
            $months[] = $startDate->month; // Format the date as "Month Year"
            $startDate->addMonth();
        }

        return $months;
    }

    function organizeDataByMonth($arrayData, $object)
    {

        $data = [];
        $object == 'month' ? $object = $this->getLast30Days() : $object = $this->getLast12Months();
        for ($i = 0; $i < sizeof($object); $i++) {
            $organizedData = 0;
            for ($index = 0; $index < sizeof($arrayData); $index++) {
                if (isset($arrayData[$index]->month) && $arrayData[$index]->month == $object[$i]) {
                    $organizedData = $arrayData[$index]->data;
                } else if (isset($arrayData[$index]->day) && $arrayData[$index]->day == $object[$i]) {
                    $organizedData = $arrayData[$index]->data;
                }
            }
            array_push($data, $organizedData);
        }
        return ["data" => $data, "monthsOrDays" => $object];
    }

    function organizeAppointmentsArray($arrayData)
    {
        $filtered = collect(json_decode($arrayData, true))->map(function ($array) {
            if (!$array['paciente']) {
                $array['paciente'] = $array['nome'];
            }
            unset($array['nome']);
            return $array;
        });

        return $filtered;
    }

    function getBusySchedules()
    {
        try {
            $data = DB::table('consultas')
                ->select('psicologo_id', 'hora', 'data')
                ->get();

            return response(['busySchedules' => $data]);
        } catch (Exception $th) {
            return response(['error' => 'Ocorreu um erro na busca ']);
        }
    }
}
