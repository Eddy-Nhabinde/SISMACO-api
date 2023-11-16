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
            $utils = new PsicologosUtils();

            $checkAvailability = $dispo->validateAvailability($request->disponibilidade);

            if ($checkAvailability == 'true') {
                $userId = $createUSer->store($request);

                if ($userId != false) {
                    $psicologo = Psicologo::create([
                        'estado' => 0,
                        'user_id' => $userId['id'],
                        'especialidade_id' => $utils->formatSpecility($request->especialidade)
                    ]);

                    $contacts = new ContactosController();
                    $contacts->store($request, $userId);

                    $dispo->store($request->disponibilidade,  $psicologo->id);

                    $mail = new MailController();
                    if (!$mail->newPsicologo($request->email, $request->nome, $userId['password']))
                        return ['error' => 'Ocorreu um erro ao enviar email ao psicólogo!'];
                    return ['success' => 'Psicólogo registado com sucesso'];
                } else return response(['error' => 'Ocorreu um Erro Inesperado!']);
            } else return response(['warning' => $checkAvailability]);
        } catch (Exception $th) {
            return response(['error' => 'Ocorreu um Erro Inesperado!']);
        }
    }

    function updateSpeciality($request)
    {
        try {
            $utils = new PsicologosUtils();

            Psicologo::where('id', $request->id)
                ->update([
                    'especialidade_id' => $utils->formatSpecility($request->especialidade)
                ]);
            return 1;
        } catch (Exception $th) {
            return 0;
        }
    }

    function update(PsychologistRequest $request)
    {
        try {
            $dispo = new DisponibilidadeController();
            $checkAvailability = $dispo->validateAvailability($request->disponibilidade);

            if ($checkAvailability == 'true') {

                $user = new UserController();
                if ($user->update($request) == 1) {

                    $contControl = new ContactosController();
                    if ($contControl->updateContacts($request) != 0) {

                        if ($this->updateSpeciality($request) != 0) {
                            if ($dispo->update($request)) return response(['success' => "Psicólogo actualizado com sucesso"]);

                            else return response(['error' => "Ocorreu um Ocorreu um Erro Inesperado"]);
                        } else return response(['error' => "Ocorreu um Ocorreu um Erro Inesperado speci"]);
                    } else return response(['error' => "Ocorreu um Ocorreu um Erro Inesperado cont"]);
                } else return response(['error' => "Ocorreu um Ocorreu um Erro Inesperado"]);
            } else return response(['warning' => $checkAvailability]);
        } catch (Exception $th) {
            dd($th);
            return response(['error' => "Ocorreu um Ocorreu um Erro Inesperado"]);
        }
    }

    function Deactivate($id, $estado)
    {
        try {
            if ($estado == 1) $estado = 0;
            else $estado = 1;

            Psicologo::where('id', $id)
                ->update([
                    'estado' => $estado,
                ]);

            return response(['success' => 'Psicólogo desactivado com sucesso']);
        } catch (Exception $th) {
            return response(['error' => 'Ocorreu um Erro Inesperado!']);
        }
    }

    function getPsychoNames()
    {
        try {
            $names = DB::table('users')
                ->join('psicologos', 'psicologos.user_id', '=', 'users.id')
                ->select('nome', 'psicologos.id')
                ->where('estado', 1)
                ->get();

            return response(["nomes" => $names]);
        } catch (Exception $th) {
            return response(['error' => 'Ocorreu um Erro Inesperado!']);
        }
    }

    function getPsicologos(Request $request)
    {
        try {
            $user = new Common();
            $utils = new PsicologosUtils();
            $dispo = new DisponibilidadeController();

            $psicologos = DB::table('psicologos')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->select('users.id as user_id', 'psicologos.id', 'users.nome', 'estado', 'email', 'especialidade_id')
                ->when($request, function ($query, $request) {
                    if (isset($request->name) && $request->name != "undefined") {
                        return $query->where('users.nome', 'like',  '%' . $request->name . '%');
                    }
                })
                ->when($request, function ($query, $request) {
                    if (isset($request->estado) && $request->estado != "undefined" && $request->estado !=  null) {
                        return $query->where('estado', $request->estado);
                    } else return $query->where('estado', 1);
                })
                ->where('users.acesso', 'psicologo')
                ->paginate($request->paging == 'false' ? 1000000000000 : 10);

            $utils = new PsicologosUtils();
            $updatedKeys = $utils->renameStatus($psicologos->items());
            $withSpeciality = $utils->formatPsychoList($updatedKeys);

            for ($i = 0; $i < sizeof($updatedKeys); $i++) {
                $updatedKeys[$i]->disponibilidade = $dispo->getDisponibilidade($updatedKeys[$i]->id);
                $updatedKeys[$i]->contactos = $user->getContacts($updatedKeys[$i]->user_id);
            }

            if ($withSpeciality != 0)  return response(["data" => $withSpeciality, "total" => $psicologos->total(), "perpage" => $psicologos->perPage()]);
            else return response(['error' => "Ocorreu um Erro Inesperado"], 200);
        } catch (Exception $th) {
            return response(['error' => "Ocorreu um Erro Inesperado"], 200);
        }
    }

    function getPsychologistDetails($id)
    {
        try {
            $utils = new PsicologosUtils();

            $psicologo = DB::table('psicologos')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->select('users.id as user', 'psicologos.id', 'users.nome', 'especialidade_id', 'email', 'estado')
                ->where('psicologos.id', $id)
                ->get();

            $withSpeciality = $utils->formatPsychoList($psicologo->toArray());

            $cont = DB::table('contactos')
                ->select('contacto', 'principal')
                ->where('user_id', $psicologo[0]->user)
                ->get();

            if ($withSpeciality != 0) return response(["psicologo" => $withSpeciality, "contactos" => $cont]);
            else return response(['error' => "Ocorreu um Erro Inesperado"], 200);
        } catch (Exception $th) {
            dd($th);
            return response(['error' => "Ocorreu um Erro Inesperado"], 200);
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
                ->select('psicologo_id', 'consultas.id', 'users.nome', 'data', 'hora', 'problemas.nome as problema', 'estado_id')
                ->where('psicologo_id', $psiID[0]->psiId)
                ->get();

            return response(["schedule" => $schedule]);
        } catch (Exception $th) {
            return response(["error" => $th->getMessage()]);
        }
    }
}
