<?php

namespace App\Http\Controllers\Utils;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ConsultasUtils
{

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
        for ($i = 0; $i < sizeof($arrayData); $i++) {
            if (!$arrayData[$i]->paciente) {
                $arrayData[$i]->paciente =  $arrayData[$i]->nome;;
            }
            unset($arrayData[$i]->nome);
        }

        return $arrayData;
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

    function getPsychologist($apppointments)
    {
        try {
            $nomes = DB::table('users')
                ->join('psicologos', 'psicologos.user_id', '=', 'users.id')
                ->select('nome', 'psicologos.id')
                ->get();

            for ($i = 0; $i < sizeof($apppointments); $i++) {
                for ($j = 0; $j < sizeof($nomes); $j++) {
                    if ($nomes[$j]->id == $apppointments[$i]->psicologo_id) {
                        $apppointments[$i]->psicologo = $nomes[$j]->nome;
                    }
                }
            }

            return $apppointments;
        } catch (\Throwable $th) {
            dd($th);
        }
    }
}
