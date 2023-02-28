<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Exception;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    function store($ocupacao, $estadoCivil, $userID, $dataNasc)
    {
        try {
            Paciente::create([
                'user_id' => $userID,
                'ocupacao' => $ocupacao,
                'estadoCivil' => $estadoCivil,
                'dataNasc' => $dataNasc
            ]);
            return 1;
        } catch (Exception $th) {
            return 0;
        }
    }
}
