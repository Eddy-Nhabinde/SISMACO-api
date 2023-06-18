<?php

namespace App\Http\Controllers\Utils;

use Exception;
use Illuminate\Support\Facades\DB;

class ConsultasUtils
{
    function organizeChartDataByEstado($chartData)
    {
        $cancelada = [];
        $pendente = [];
        $realizada = [];

        foreach ($chartData as $key) {
            switch ($key->estado) {
                case 'Pendente':
                    array_push($pendente, $key);
                    break;
                case 'Cancelada':
                    array_push($cancelada, $key);
                    break;
                case 'Realizada':
                    array_push($realizada, $key);
                    break;
            }
        }

        return [
            "canceladas" => $this->organizeDataByMonth($cancelada),
            "pendentes" => $this->organizeDataByMonth($pendente),
            "realizadas" => $this->organizeDataByMonth($realizada),
        ];
    }

    function organizeDataByMonth($arrayData)
    {
        $data = [];
        for ($i = 0; $i < 12; $i++) {
            $monthData = 0;
            for ($index = 0; $index < sizeof($arrayData); $index++) {
                if ($arrayData[$index]->month == $i) {
                    $monthData = $arrayData[$index]->data;
                }
            }
            array_push($data, $monthData);
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
