<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Utils\Common;
use App\Http\Controllers\Utils\PsicologosUtils;
use App\Http\Requests\PsychologistRequest;
use App\Models\Psicologo;
use App\Models\User;
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

    function store(PsychologistRequest $request)
    {
        try {
            $createUSer = new UserController();
            $dispo = new DisponibilidadeController();
            $checkAvailability = $dispo->validateAvailability($request->disponibilidade);

            if ($checkAvailability == 'true') {
                $userId = $createUSer->store($request);

                if ($userId != false) {
                    $psicologo = Psicologo::create([
                        'user_id' => $userId['id'],
                        'especialidade_id' => $request->especialidade
                    ]);

                    $contacts = new ContactosController();
                    $contacts->store($request, $userId);

                    $dispo->store($request->disponibilidade,  $psicologo->id);

                    $mail = new MailController();
                    if (!$mail->newPsicologo($request->email, $request->nome, $userId['password']))
                        return ['error' => 'Ocorreu um erro ao enviar email ao psicólogo!'];
                    return ['success' => 'Psicólogo registado com sucesso'];

                } else return response(['error' => 'Erro inesperado!']);
            } else return response(['warning' => $checkAvailability]);
        } catch (Exception $th) {
            dd($th);
        }
    }

    function RollBack($user_id)
    {
        try {
            User::where('id', $user_id)->delete();
            return true;
        } catch (Exception $th) {
            return false;
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
            return response(['error' => "Erro Inesperado"], 200);
        }
    }

    function getPsicologos(Request $request)
    {
        try {
            $psicologos = DB::table('psicologos')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->join('especialidades', 'especialidades.id', '=', 'psicologos.especialidade_id')
                ->select('psicologos.id', 'users.nome', 'especialidades.nome as especialidade', 'estado')
                ->where('users.acesso', 'psicologo')
                ->paginate($request->paging == 'false' ? 1000000000000 : 10);

            $utils = new PsicologosUtils();
            $updatedKeys = $utils->renameStatus($psicologos->items());

            for ($i = 0; $i < sizeof($updatedKeys); $i++) {
                $updatedKeys[$i]->disponibilidade = $this->getDisponibilidade($updatedKeys[$i]->id);
            }

            return response(["data" => $updatedKeys, "total" => $psicologos->total()]);
        } catch (Exception $th) {
            return response(['error' => "Erro Inesperado"], 200);
        }
    }


    function getDisponibilidade($id)
    {
        if (isset($id)) {
            $disponibilidade =  DB::table('disponibilidades')
                ->select('diaDaSemana', 'inicio', 'fim')
                ->where('psicologo_id', $id)
                ->get();

            return $disponibilidade;
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
