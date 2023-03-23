<?php

namespace App\Http\Controllers;

use App\Models\Consulta;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ConsultaController extends Controller
{
    function novaConsulta(Request $request)
    {
        if ($this->validating($request)) {
            $user = JWTAuth::parseToken()->authenticate();

            $cons = Consulta::create([
                "psicologo_id" => $request->psicologo,
                "paciente_id" => $user->id,
                "problema_id" => 1,
                "descricaoProblema" => "testando",
                "estado_id" => 1,
                "data" => Carbon::parse($request->data)->format('Y-m-d'),
                "hora" => $request->hora
            ]);

            if ($this->sendMail($request, Carbon::parse($request->data)->format('Y-m-d')) == 0) {
                Consulta::where('id', $cons->id)->delete();

                return response(["error" => "Erro inesperado!"]);
            } else {
                return response(["success" => "Consulta marcada com sucesso!"]);
            }
        } else {
            return response(["warning" => "Por favor preencha todos os campos!"]);
        }
    }

    function getPacienteAppointments()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $appointments = DB::table('consultas')
                ->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->where('paciente_id', $user->id)
                ->select('users.nome as psiNome', 'hora', 'data', 'estados.nome')
                ->get();

            return response(["consultas" => $appointments]);
        } catch (Exception $th) {
            dd($th);
            return response(["error" => "Erro inesperado!"]);
        }
    }

    function sendMail($request, $data)
    {
        try {
            $email = DB::table('users')
                ->join('psicologos', 'psicologos.user_id', '=', 'users.id')
                ->where('psicologos.id', $request->psicologo)
                ->select('email', 'nome')
                ->get();

            $mail = new MailController();
            return $mail->newAppointment($email[0]->email, $data, $request->hora, $email[0]->nome);
        } catch (Exception $th) {
            return 0;
        }
    }

    function validating($request)
    {
        try {
            $request->validate([
                'hora' => 'required',
                'nome' => 'required',
                'apelido' => 'required',
                'email' => 'required',
                'contacto1' => 'required',
                'psicologo' => 'required',
                'data' => 'required'
            ]);
            return true;
        } catch (\Illuminate\Validation\ValidationException $th) {
            return false;
        }
    }
}
