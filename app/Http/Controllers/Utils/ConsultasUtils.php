<?php

namespace App\Http\Controllers\Utils;

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

    function organizeDataByMonth($arrayData, $object)
    {
        // dd($arrayData);
        $data = [];
        $object == 'month' ? $object = 30 : $object = 12;
        for ($i = 0; $i < $object; $i++) {
            $organizedData = 0;
            for ($index = 0; $index < sizeof($arrayData); $index++) {
                if (isset($arrayData[$index]->month) && $arrayData[$index]->month == $i + 1) {
                    $organizedData = $arrayData[$index]->data;
                } else if (isset($arrayData[$index]->day) && $arrayData[$index]->day == $i + 1) {
                    $organizedData = $arrayData[$index]->data;
                }
            }
            array_push($data, $organizedData);
        }
        return $data;
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
