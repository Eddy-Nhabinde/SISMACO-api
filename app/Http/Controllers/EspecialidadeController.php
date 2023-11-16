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
            $especialidades = DB::table('especialidades')
                ->select('id as value', 'nome as label')
                ->get();

            return response(['especialidades' => $especialidades]);
        } catch (Exception $th) {
            return response(['error' => "Ocorreu um Erro Inesperado!"], 200);
        }
    }
}
