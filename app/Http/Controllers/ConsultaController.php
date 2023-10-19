<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Utils\Common;
use App\Http\Controllers\Utils\ConsultasUtils;
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
        dd($request);
        if (!$request->admin) {
            $cons = Consulta::create([
                "psicologo_id" => $request->psicologo,
                "paciente_id" =>  $this->userId,
                "problema_id" => 1,
                "descricaoProblema" => "testando",
                "estado_id" => 1,
                "data" => Carbon::parse($request->data)->addDay()->format('Y-m-d'),
                "hora" => $request->hora
            ]);
        } else {
            $cons = Consulta::create([
                "psicologo_id" => $request->psicologo,
                "problema_id" => 1,
                "estado_id" => 1,
                "data" => Carbon::parse($request->data)->addDay()->format('Y-m-d'),
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
                    'data' => Carbon::parse($request->data)->addDay()->format('Y-m-d'),
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
                    }
                })
                ->where('estados.id', $estado)
                ->paginate($request->paging == 'false' ? 1000000000000 : 10);

            $utils = new ConsultasUtils();
            return response(['consultas' => $utils->organizeAppointmentsArray($data->items()), "total" => $data->total()]);
        } catch (Exception $th) {
            dd($th);
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
