<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EspecialidadeController extends Controller
{
    function getEspecialidade()
    {
        try {
            $psicologos = DB::table('especialidades')
                ->select('id', 'nome')
                ->get();

            return response(['especialidades' => $this->getDisponibilidade($psicologos)]);
        } catch (Exception $th) {
            return response(['error' => "Erro inesperado!"], 200);
        }
    }
}
