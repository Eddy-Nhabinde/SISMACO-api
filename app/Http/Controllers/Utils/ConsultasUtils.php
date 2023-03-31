<?php

namespace App\Http\Controllers\Utils;

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
}
