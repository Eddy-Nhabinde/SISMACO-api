<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    function store($request, $userID)
    {
        try {
            if ($this->validating($request)) {
                Paciente::create([
                    'user_id' => $userID,
                    'ocupacao' => $request->ocupacao,
                    'estadoCivil' => $request->estadoCivil,
                    'dataNasc' => Carbon::parse($request->dataNasc)->format('Y-m-d'),
                    'sexo' => $request->sexo
                ]);

                $contacts = new ContactosController();
                $contacts->store($request, $userID);
                return 1;
            } else {
                return 0;
            }
        } catch (Exception $th) {
            return 0;
        }
    }

    function validating($request)
    {
        try {
            $request->validate([
                'ocupacao' => 'exclude_if:paciente,false|required',
                'estadoCivil' => 'exclude_if:paciente,false|required',
                'dataNasc' => 'exclude_if:paciente,false|required',
                'sexo' => 'required',
                'contacto1' => 'required',
            ]);
            return true;
        } catch (\Illuminate\Validation\ValidationException $th) {
            dd($th->getMessage());
            return false;
        }
    }
}
