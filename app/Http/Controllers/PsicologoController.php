<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Utils\Common;
use App\Http\Controllers\Utils\PsicologosUtils;
use App\Models\Psicologo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PsicologoController extends Controller
{
    private $userId = 0;

    public function __construct()
    {
        $user = new Common();
        $this->userId = $user->getUserId();
    }

    function store($userid, $request, $password)
    {
        try {
            if ($this->validating($request)) {
                $psicologo = Psicologo::create([
                    'user_id' => $userid,
                    'especialidade_id' => $request->especialidade
                ]);

                $contacts = new ContactosController();
                $contacts->store($request, $userid);

                $dispo = new DisponibilidadeController();
                $dispo->store($request->disponibilidade,  $psicologo->id);

                $mail = new MailController();
                if (!$mail->newPsicologo($request->email, $request->nome, $password)) {
                    return 0;
                } else {
                    return 1;
                }
            } else {
                return 0;
            }
            return 1;
        } catch (Exception $th) {
            return 0;
        }
    }

    function getPsychologistDetails($id)
    {
        try {
            $psicologo = DB::table('psicologos')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->join('especialidades', 'especialidades.id', '=', 'psicologos.especialidade_id')
                ->select('users.id as user', 'psicologos.id', 'users.nome', 'especialidades.nome as especialidade', 'email', 'estado')
                ->where('psicologos.id', $id)
                ->get();

            $cont = DB::table('contactos')
                ->select('contacto', 'principal')
                ->where('user_id', $psicologo[0]->user)
                ->get();

            $consCont = new DashboardController();
            return response(["psicologo" => $psicologo, "contactos" => $cont, "consultas" => $consCont->getDashBoardData(date('Y'), $psicologo[0]->id)->original]);
        } catch (Exception $th) {
            dd($th);
            return response(['error' => "Erro Inesperado"], 200);
        }
    }

    function getPsicologos()
    {
        try {
            $psicologos = DB::table('psicologos')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->join('especialidades', 'especialidades.id', '=', 'psicologos.especialidade_id')
                ->select('psicologos.id', 'users.nome', 'especialidades.nome as especialidade', 'estado')
                ->where('users.acesso', 'psicologo')
                ->paginate(10);

            $utils = new PsicologosUtils();
            return response(["data" => $this->getDisponibilidade($utils->renameStatus($psicologos->items())), "total" => $psicologos->total()]);
        } catch (Exception $th) {
            return response(['error' => "Erro Inesperado"], 200);
        }
    }


    function getDisponibilidade($psicologos)
    {
        if (isset($psicologos[0])) {
            $psicologos[0]->disponibilidade =  DB::table('disponibilidades')
                ->select('diaDaSemana', 'inicio', 'fim')
                ->where('psicologo_id', $psicologos[0]->id)
                ->get();

            return ['psicologos' => $psicologos];
        } else {
            return ['warning' => "Nao ha psicologos registados!"];
        }
    }

    function AlterarEstado($id, $estadoId)
    {
        try {
            Psicologo::where('id', $id)
                ->update([
                    'estado' => $estadoId
                ]);
            return ['success' => $estadoId == 1 ? 'Psicologo activado com sucesso' : 'Psicologo desativado com sucesso!'];
        } catch (Exception $th) {
            return ['error' => "Erro inesperado!"];
        }
    }

    function getSchedule()
    {
        try {
            $psiID =  DB::table('users')
                ->join('psicologos', 'psicologos.user_id', '=', 'users.id')
                ->select('psicologos.id as psiId')
                ->where('users.id', $this->userId)
                ->get();

            $schedule = DB::table('consultas')
                ->leftJoin('users', 'users.id', '=', 'consultas.paciente_id')
                ->join('problemas', 'problemas.id', '=', 'consultas.problema_id')
                ->select('psicologo_id', 'consultas.id', 'users.nome', 'data', 'hora', 'problemas.nome as problema')
                ->where('psicologo_id', $psiID[0]->psiId)
                ->get();

            return response(["schedule" => $schedule]);
        } catch (Exception $th) {
            return response(["error" => $th->getMessage()]);
        }
    }

    function validating($request)
    {
        try {
            $request->validate([
                'especialidade' => 'required',
                'contacto1' => 'required',
                'disponibilidade' => 'required'
            ]);
            return true;
        } catch (\Illuminate\Validation\ValidationException $th) {
            return false;
        }
    }
}
