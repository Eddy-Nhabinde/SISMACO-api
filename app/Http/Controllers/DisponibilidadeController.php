<?php

namespace App\Http\Controllers;

use App\Models\Disponibilidade;
use Exception;
use Illuminate\Http\Request;

class DisponibilidadeController extends Controller
{
    function store($dispo, $user_id)
    {
        try {
            $dias = array_keys($dispo);
            $disponibilidade = [];

            for ($i = 0; $i < sizeof($dias); $i++) {
                $disponibilidade[] = ["diaDaSemana" => $dias[$i], "inicio" => $dispo[$dias[$i]]['Inicio'], "fim" => $dispo[$dias[$i]]['Fim'], "psicologo_id" => $user_id];
            }

            Disponibilidade::insert($disponibilidade);
            return true;
        } catch (Exception $th) {
            return false;
        }
    }

    function validateAvailability($availability)
    {
        $dias = array_keys($availability);

        $inicio = [
            "08:30",
            "10:00",
            "11:30",
            "13:00",
            "14:30"
        ];

        $fim = [
            "09:50",
            "11:20",
            "12:50",
            "14:20",
            "15:50"
        ];

        $days = [
            "Segunda-feira",
            "Terça-feira",
            "Quarta-feira",
            "Quinta-feira",
            "Sexta-feira",
            "Segunda a sexta"
        ];

        for ($i = 0; $i < sizeof($dias); $i++) {
            if (isset($availability[$dias[$i]]['Inicio']) && isset($availability[$dias[$i]]['Fim'])) {
                $begin = array_search($availability[$dias[$i]]['Inicio'], $inicio);
                $end = array_search($availability[$dias[$i]]['Fim'], $fim);
                if (gettype($begin) != "boolean" && gettype($end) != "boolean") {
                    if ($begin > $end) return "A hora de início não pode ser maior que a hora do fim na " . $days[$i];
                } else return "Horas inválidas na " . $days[$i];
            } else return "Por favor, selecione o Inicio e/ou Fim da " . $days[$i];
        }
        return 'true';
    }
}
