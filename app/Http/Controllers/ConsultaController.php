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

    function getPsicologId()
    {
        try {
            $id = DB::table('psicologos')
                ->where('psicologos.user_id', $this->userId)
                ->select('id')
                ->get();

            return $id[0]->id;
        } catch (Exception $th) {
            return 0;
        }
    }

    function novaConsulta(Request $request)
    {
        if (!$request->admin) {
            $cons = Consulta::create([
                "psicologo_id" => $request->psicologo,
                "paciente_id" =>  $this->userId,
                "problema_id" => 1,
                "descricaoProblema" => "testando",
                "estado_id" => 1,
                "data" => Carbon::parse($request->data)->format('Y-m-d'),
                "hora" => $request->hora
            ]);
        } else {
            $cons = Consulta::create([
                "psicologo_id" => $request->psicologo,
                "problema_id" => 1,
                "descricaoProblema" => "testando",
                "estado_id" => 1,
                "data" => Carbon::parse($request->data)->format('Y-m-d'),
                "hora" => $request->hora,
                "nome" => $request->nome,
                "apelido" => $request->apelido,
                "email" => $request->email,
                "contacto1" => $request->contacto1,
                "contacto2" => $request->contacto2
            ]);
        }
        if ($this->sendMail($request, Carbon::parse($request->data)->format('Y-m-d')) == 0) {
            Consulta::where('id', $cons->id)->delete();
            return response(["error" => "Erro inesperado!"]);
        } else {
            return response(["success" => "Consulta marcada com sucesso!"]);
        }
    }

    function Reschedule(Request $request)
    {
        try {
            $psyData = DB::table('consultas')
                ->join('users', 'users.id', '=', 'consultas.psicologo_id')
                ->select('users.nome', 'users.email')
                ->where('consultas.id', $request->id)
                ->get();

            Consulta::where('id', $request->id)
                ->update([
                    'data' => Carbon::parse($request->data)->format('Y-m-d'),
                    'hora' => $request->hora
                ]);

            $mail = new MailController();
            if ($mail->RescheduleAppointment($psyData, $request) == 1) {
                return response(["success" => "Consulta remarcada com sucesso!"]);
            } else {
                return response(["error" => "Erro inesperado"]);
            }
            return response(['success' => 'Consulta Remarcada com sucesso !']);
        } catch (Exception $th) {
            return response(['error' => $th]);
        }
    }

    function CloseAppointment($id)
    {
        try {
            Consulta::where('id', $id)
                ->update([
                    'estado_id' => 3,
                ]);
            return response(["success" => "Consulta concluida com sucesso!"]);
        } catch (Exception $th) {
            return response(["error" => "Erro inesperado"]);
        }
    }


    function cancelAppointment($id)
    {
        try {
            $appointmentData = DB::table('consultas')
                ->join('users', 'users.id', '=', 'consultas.paciente_id')
                ->select('users.nome', 'hora', 'data', 'users.email')
                ->where('consultas.id', $id)
                ->get();
            $mail = new MailController();

            if ($mail->cancelAppointment($appointmentData) == 1) {
                Consulta::where('id', $id)
                    ->update([
                        'estado_id' => 2,
                    ]);
            } else {
                return response(["error" => "Erro inesperado"]);
            }
            return response(["success" => "Consulta cancelada com sucesso!"]);
        } catch (Exception $th) {
            return response(["error" => "Erro inesperado"]);
        }
    }

    function getAppointments($estado)
    {
        try {
            $data = DB::table('consultas')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id')
                ->leftJoin('users', 'users.id', '=', 'consultas.paciente_id')
                ->select('consultas.id', 'users.nome as paciente', 'hora', 'data', 'estados.nome as estado', DB::raw("CONCAT(consultas.nome,' ',consultas.apelido) AS nome"))
                ->when($this->acesso, function ($query) {
                    if ($this->acesso == 'psicologo') {
                        return $query->where('psicologos.user_id', $this->userId);
                    }
                })
                ->where('estados.id', $estado)
                ->get();

            $utils = new ConsultasUtils();
            return response(['consultas' => $utils->organizeAppointmentsArray($data)]);
        } catch (Exception $th) {
            return response(["error" => "Erro inesperado!"]);
        }
    }

    function getDashBoardData($id = null)
    {
        try {
            $data = DB::table('consultas')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->when($this->acesso || $id, function ($query, $id) {
                    if ($this->acesso == 'psicologo' || $id != null) {
                        return $query->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id');
                    }
                })
                ->select('estados.nome as estado', DB::raw('COUNT(estado_id) as total'))
                ->when($this->acesso, function ($query) {
                    if ($this->acesso == 'psicologo') {
                        return $query->where('psicologos.id', $this->getPsicologId());
                    }
                })
                ->when($id, function ($query, $id) {
                    if ($id != null) {
                        return $query->where('psicologos.id', $id);
                    }
                })
                ->groupBy('estado')
                ->groupBy('estados.nome')
                ->get();

            $chartData = DB::table('consultas')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->when($this->acesso || $id, function ($query, $id) {
                    if ($this->acesso == 'psicologo' || $id != null) {
                        return $query->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id');
                    }
                })
                ->select('estados.nome as estado', DB::raw('count(consultas.id) as `data`'),  DB::raw('MONTH(data) month'))
                ->when($this->acesso, function ($query) {
                    if ($this->acesso == 'psicologo') {
                        return $query->where('psicologos.id', $this->getPsicologId());
                    }
                })
                ->when($id, function ($query, $id) {
                    if ($id != null) {
                        return $query->where('psicologos.id', $id);
                    }
                })
                ->groupby('month')
                ->groupBy('estado')
                ->groupBy('estados.nome')
                ->get();

            $utils = new ConsultasUtils();

            if ($this->acesso != 'psicologo') {
                $user = new UserController();
                return response(["dashData" => ['consultas' => $data, "users" => $user->getPacientesCount(), "chartData" => $utils->organizeChartDataByEstado($chartData)]]);
            }

            return response(["dashData" => ['consultas' => $data, "chartData" => $utils->organizeChartDataByEstado($chartData)]]);
        } catch (Exception $th) {
            return response(["error" => "Erro inesperado!" . $th]);
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
                ->select('consultas.id', 'estados.id as estadoId', 'users.nome as psiNome', 'hora', 'data', 'estados.nome as estado', 'users.id as psiId')
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
}
