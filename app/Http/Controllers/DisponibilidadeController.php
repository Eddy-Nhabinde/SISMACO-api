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
        } catch (Exception $th) {
            return 1;
        }
    }
}