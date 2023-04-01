<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Utils\Common;
use App\Models\Psicologo;
use Exception;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class PsicologoController extends Controller
{
    private $userId = 0;

    public function __construct()
    {
        $user = new Common();
        $this->userId = $user->getUserId();
    }

    function store($userid, $request)
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
                if (!$mail->newPsicologo($request->email, $request->nome)) {
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


    function getPsicologos()
    {
        try {
            $psicologos = DB::table('psicologos')
                ->join('users', 'users.id', '=', 'psicologos.user_id')
                ->select('psicologos.id', 'nome as label', 'especialidade')
                ->get();

            return response(['psicologos' => $this->getDisponibilidade($psicologos)]);
        } catch (Exception $th) {
            return response(['error' => $th], 200);
        }
    }


    function getDisponibilidade($psicologos)
    {
        $psicologos[0]->disponibilidade =  DB::table('disponibilidades')
            ->select('diaDaSemana', 'inicio', 'fim')
            ->where('psicologo_id', $psicologos[0]->id)
            ->get();

        return  $psicologos;
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
                ->select('users.nome', 'data', 'hora', 'problemas.nome as problema')
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
