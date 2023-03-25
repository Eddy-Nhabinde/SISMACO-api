<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\UserController;
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

    function getDashBoardData()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $data = DB::table('consultas')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->when($user, function ($query, $user) {
                    if ($user->acesso == 'psicologo') {
                        return $query->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id');
                    }
                })
                ->select(array('estados.nome as estado', DB::raw('COUNT(estado_id) as total')))
                ->groupBy('estado')
                ->get();

            $chartData = DB::table('consultas')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->when($user, function ($query, $user) {
                    if ($user->acesso == 'psicologo') {
                        return $query->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id');
                    }
                })
                ->select('estados.nome as estado', DB::raw('count(consultas.id) as `data`'),  DB::raw('MONTH(data) month'),)
                ->groupby('month')
                ->groupBy('estado')
                ->get();

            if ($user->acesso != 'psicologo') {
                $user = new UserController();
                return response(["dashData" => $data, "users" => $user->getPacientesCount()]);
            }

            return response(["dashData" => ['consultas' => $data, "chartData" => $this->organizeChartDataByEstado($chartData)]]);
        } catch (Exception $th) {
            return response(["error" => "Erro inesperado!"]);
        }
    }


    function organizeChartDataByEstado($chartData)
    {
        $cancelada = [];
        $pendente = [];
        $realizada = [];

        foreach ($chartData as $key) {
            switch ($key->estado) {
                case 'Pendente':
                    array_push($pendente, $key);
                    break;
                case 'Cancelada':
                    array_push($cancelada, $key);
                    break;
                case 'Realizada':
                    array_push($realizada, $key);
                    break;
            }
        }

        return [
            "canceladas" => $this->organizeDataByMonth($cancelada),
            "pendentes" => $this->organizeDataByMonth($pendente),
            "realizadas" => $this->organizeDataByMonth($realizada),
        ];
    }

    function organizeDataByMonth($arrayData)
    {
        $data = [];
        for ($i = 0; $i < 12; $i++) {
            $monthData = 0;
            for ($index = 0; $index < sizeof($arrayData); $index++) {
                if ($arrayData[$index]->month == $i) {
                    $monthData = $arrayData[$index]->data;
                }
            }
            array_push($data, $monthData);
        }
        return $data;
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
