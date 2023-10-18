<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\DB;

class ProblemaController extends Controller
{
    function getProblems()
    {
        try {
            $problemas = DB::table('problemas')
                ->select('id as value', 'nome as label')
                ->get();

            return  response(["problemas" => $problemas]);
        } catch (Exception $th) {
            return  response(["error" => "Erro inesperado!"]);
        }
    }
}
