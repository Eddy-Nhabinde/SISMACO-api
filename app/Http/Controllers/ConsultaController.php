<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Utils\Common;
use App\Http\Controllers\Utils\ConsultasUtils;
use App\Models\Consulta;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultaController extends Controller
{
    private $userId = 0;
    private $acesso = 0;

    public function __construct()
    {
        $user = new Common();
        $this->userId = $user->getUserId();
        $this->acesso = $user->getAcesso();
    }

    function novaConsulta(Request $request)
    {
        if ($this->validating($request)) {
            $cons = Consulta::create([
                "psicologo_id" => $request->psicologo,
                "paciente_id" =>  $this->userId,
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

    function getAppointments($estado)
    {
        try {
            $data = DB::table('consultas')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->select('users.nome as paciente', 'hora', 'data', 'estados.nome as estado')
                ->where('users.id', $this->userId)
                ->where('estados.id', $estado)
                ->get();

            return response(['consultas' => $data]);
        } catch (Exception $th) {
            return response(["error" => "Erro inesperado!"]);
        }
    }

    function getDashBoardData()
    {
        try {
            $data = DB::table('consultas')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->when(function ($query) {
                    if ($this->acesso == 'psicologo') {
                        return $query->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id');
                    }
                })
                ->select(array('estados.nome as estado', DB::raw('COUNT(estado_id) as total')))
                ->groupBy('estado')
                ->get();

            $chartData = DB::table('consultas')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->when(function ($query) {
                    if ($this->acesso == 'psicologo') {
                        return $query->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id');
                    }
                })
                ->select('estados.nome as estado', DB::raw('count(consultas.id) as `data`'),  DB::raw('MONTH(data) month'),)
                ->groupby('month')
                ->groupBy('estado')
                ->get();

            if ($this->acesso != 'psicologo') {
                $user = new UserController();
                return response(["dashData" => $data, "users" => $user->getPacientesCount()]);
            }

            $utils = new ConsultasUtils();
            return response(["dashData" => ['consultas' => $data, "chartData" => $utils->organizeChartDataByEstado($chartData)]]);
        } catch (Exception $th) {
            return response(["error" => "Erro inesperado!"]);
        }
    }

    function getPacienteAppointments()
    {
        try {
            $appointments = DB::table('consultas')
                ->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->where('paciente_id', $this->userId)
                ->select('users.nome as psiNome', 'hora', 'data', 'estados.nome')
                ->get();

            return response(["consultas" => $appointments]);
        } catch (Exception $th) {
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
