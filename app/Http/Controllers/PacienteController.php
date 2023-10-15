<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

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

    function historico($paciente_id)
    {
        try {
            $data = DB::table('consultas')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id')
                ->leftJoin('users', 'users.id', '=', 'psicologos.user_id')
                ->select('users.nome as psicologo', 'hora', 'data')
                ->where('consultas.paciente_id', $paciente_id)
                ->paginate(5);

            return response(['history' => $data->items(), "total" => $data->total()]);
        } catch (Exception $ex) {
            dd($ex);
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
            return false;
        }
    }
}
