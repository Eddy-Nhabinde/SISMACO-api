<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\UserController;
use App\Http\Requests\UserRegisterRequest;
use App\Models\Paciente;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class PacienteController extends Controller
{
    function store(UserRegisterRequest $request)
    {
        try {
            $createUser = new UserController();
            $userId = $createUser->store($request);

            if ($userId != false) {
                Paciente::create([
                    'user_id' => $userId['id'],
                    'ocupacao' => $request->ocupacao,
                    'estadoCivil' => $request->estadoCivil,
                    'dataNasc' => Carbon::parse($request->dataNasc)->format('Y-m-d'),
                    'sexo' => $request->sexo
                ]);

                $contacts = new ContactosController();
                $contacts->store($request, $userId['id']);

                return response(['newUser' => 1]);
            } else return response(['error' => 'Erro inesperado!']);
        } catch (Exception $th) {
            return response(['error' => 'Erro inesperado!']);
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
            return response(["error" => "Erro inesperado"]);
        }
    }
}
