<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Utils\Common;
use App\Http\Controllers\Utils\ConsultasUtils;
use App\Http\Controllers\Utils\PsicologosUtils;
use App\Http\Requests\NewAppRequest;
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

    function novaConsulta(NewAppRequest $request)
    {
        try {
            $cUtils = new ConsultasUtils();
            if ($cUtils->checkIfUserHasPendentApp($this->userId) == false && $this->acesso != 'admin') {
                if ($this->acesso != 'admin') {
                    Consulta::create([
                        "psicologo_id" => $request->psicologo,
                        "paciente_id" =>  $this->userId,
                        "problema_id" => $request->problema,
                        "estado_id" => 1,
                        "data" => Carbon::parse($request->data)->format('Y-m-d'),
                        "hora" => $request->hora,
                        "tipoConsulta" => $request->tipoConsulta,
                        "tipoPaciente" => $request->tipoPaciente
                    ]);
                } else {
                    Consulta::create([
                        "psicologo_id" => $request->psicologo,
                        "problema_id" => $request->problema,
                        "estado_id" => 1,
                        "data" => Carbon::parse($request->data)->format('Y-m-d'),
                        "hora" => $request->hora,
                        "nome" => $request->nome,
                        "apelido" => $request->apelido,
                        "email" => $request->email,
                        "contacto1" => $request->contacto1,
                        "contacto2" => $request->contacto2,
                        "tipoConsulta" => $request->tipoConsulta,
                        "tipoPaciente" => $request->tipoPaciente
                    ]);
                }
                if ($this->acesso == 'paciente') {
                    $email = DB::table('users')
                        ->select('email')
                        ->where('id', $this->userId)
                        ->get();

                    $this->sendMail($request, Carbon::parse($request->data)->format('Y-m-d'), $email[0]->email);
                } else {
                    $this->sendMail($request, Carbon::parse($request->data)->format('Y-m-d'), $request->email);
                }
                return response(["success" => "Consulta marcada com sucesso!"]);
            } else {
                return response(["warning" => "Tens uma consulta pendente!"]);
            }
        } catch (Exception $th) {
            dd($th);
            return response(["error" => "Ocorreu um Erro Inesperado!"]);
        }
    }

    function ConsultaDeSegmento($dados, $request)
    {
        Consulta::create([
            "paciente_id" =>  $dados[0]->paciente_id,
            "psicologo_id" => $request->psicologo,
            "problema_id" => $dados[0]->problema_id,
            "estado_id" => 1,
            "data" => Carbon::parse($request->data)->format('Y-m-d'),
            "hora" => $request->hora,
            "nome" => $dados[0]->nome,
            "apelido" => $dados[0]->apelido,
            "email" => $dados[0]->email,
            "contacto1" => $dados[0]->contacto,
            "contacto2" => $dados[0]->contacto2
        ]);
    }

    function Reschedule(Request $request)
    {
        try {
            if (isset($request->segmento) && $request->segmento == true) {
                $row = $this->CloseAppointment($request->id, true);
                $this->ConsultaDeSegmento($row, $request);
            } else {
                Consulta::where('id', $request->id)
                    ->update([
                        'data' => Carbon::parse($request->data)->format('Y-m-d'),
                        'hora' => $request->hora
                    ]);
            }

            $paciente = DB::table('consultas')
                ->join('pacientes', 'pacientes.id', '=', 'consultas.paciente_id')
                ->join('users', 'users.id', '=', 'pacientes.user_id')
                ->select('users.nome', 'users.email')
                ->where('consultas.id', $request->id)
                ->get();

            $psyData = DB::table('consultas')
                ->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->select('users.nome', 'users.email')
                ->where('consultas.id', $request->id)
                ->get();

            $mail = new MailController();
            if ($mail->RescheduleAppointment($psyData, $request, $paciente) == 1) {
                if ($request->segmento == true)
                    return response(["success" => "Próxima consulta marcada com sucesso!"]);
                else return response(["success" => "Consulta remarcada com sucesso!"]);
            } else {
                return response(["error" => "Ocorreu um Erro Inesperado"]);
            }
        } catch (Exception $th) {
            return response(['error' => "Erro inesprado!"]);
        }
    }

    function CloseAppointment($id, $segmento = false)
    {
        try {
            Consulta::where('id', $id)
                ->update([
                    'estado_id' => 3,
                ]);

            if ($segmento == true) {
                $row = DB::table('consultas')
                    ->where('id', $id)
                    ->get();

                return $row;
            } else return response(["success" => "Consulta concluida com sucesso!"]);
        } catch (Exception $th) {
            return response(["error" => "Ocorreu um Erro Inesperado"]);
        }
    }

    function cancelAppointment($id)
    {
        try {
            $appointmentData = DB::table('consultas')
                ->join('pacientes', 'pacientes.id', '=', 'consultas.paciente_id')
                ->join('users', 'users.id', '=', 'pacientes.user_id')
                ->select('users.nome', 'hora', 'data', 'users.email')
                ->where('consultas.id', $id)
                ->get();

            $psyData = DB::table('consultas')
                ->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->select('users.nome', 'users.email')
                ->where('consultas.id', $id)
                ->get();

            $mail = new MailController();
            $cancel = $mail->cancelAppointment($appointmentData, $psyData);

            if ($cancel == 1) {
                Consulta::where('id', $id)
                    ->update([
                        'estado_id' => 2,
                    ]);
            } else {
                return response(["error" => "Ocorreu um Erro Inesperado"]);
            }
            return response(["success" => "Consulta cancelada com sucesso!"]);
        } catch (Exception $th) {
            dd($th);
            return response(["error" => "Ocorreu um Erro Inesperado"]);
        }
    }

    function getAppointments(Request $request, $estado)
    {
        try {
            $data = DB::table('consultas')
                ->join('estados', 'estados.id', '=', 'consultas.estado_id')
                ->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id')
                ->join('problemas', 'problemas.id', '=', 'consultas.problema_id')
                ->leftJoin('users', 'users.id', '=', 'consultas.paciente_id')
                ->select('consultas.paciente_id', 'users.id as user_id', 'problemas.nome as problema', 'consultas.psicologo_id', 'consultas.id', 'users.nome as paciente', 'hora', 'data', 'estados.nome as estado', DB::raw("CONCAT(consultas.nome,' ',consultas.apelido) AS nome"))
                ->when($this->acesso, function ($query) {
                    if ($this->acesso == 'psicologo') {
                        return $query->where('psicologos.user_id', $this->userId);
                    } else if ($this->acesso == 'paciente') {
                        $paciente = new Common();
                        return $query->where('consultas.paciente_id', $paciente->getPacienteId($this->userId));
                    }
                })
                ->when($request, function ($query, $request) {
                    $ids = explode(",", $request->ids);
                    if (sizeof($ids) > 0 && isset($request->ids)) {
                        return $query->whereIn('psicologos.id', $ids);
                    }
                })
                ->when($request, function ($query, $request) {
                    if (isset($request->name) && $request->name != "undefined") {
                        return $query->where('users.nome', 'like',  '%' . $request->name . '%');
                    }
                })
                ->where('estados.id', $estado)
                ->paginate($request->paging == 'false' ? 1000000000000 : 10);

            $psicologos = new ConsultasUtils();
            $organizedData = $psicologos->getPsychologist($data);

            $utils = new ConsultasUtils();
            return response(['consultas' => $utils->organizeAppointmentsArray($organizedData->items()), "total" => $organizedData->total(), "perpage" => $organizedData->perPage()]);
        } catch (Exception $th) {
            return response(["error" => "Ocorreu um Erro Inesperado!"]);
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
            return response(["error" => "Ocorreu um Erro Inesperado!"]);
        }
    }

    function sendMail($request, $data, $paciente)
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
