<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConsultaController extends Controller
{
    function novaConsulta(Request $request)
    {
    }

    function validating($request)
    {
        try {
            $request->validate([
                'ocupacao' => 'exclude_if:paciente,false|required',
                'estadoCivil' => 'exclude_if:paciente,false|required',
                'dataNasc' => 'exclude_if:paciente,false|required',
                'sexo' => 'required',
                'contacto1' => 'required'
            ]);
            return true;
        } catch (\Illuminate\Validation\ValidationException $th) {
            return false;
        }
    }
}
