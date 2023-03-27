<?php

namespace App\Http\Controllers;

use App\Models\Disponibilidade;
use App\Models\Psicologo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class PsicologoController extends Controller
{
    function store($userid, $request)
    {
        try {
            if ($this->validating($request)) {
                $psicologo = Psicologo::create([
                    'user_id' => $userid,
                    'especialidade' => $request->especialidade
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
            $user = JWTAuth::parseToken()->authenticate();
            
            $psiID =  DB::table('users')
                ->join('psicologos', 'psicologos.user_id', '=', 'users.id')
                ->select('psicologos.id as psiId')
                ->where('users.id', $user->id)
                ->get();

            $schedule = DB::table('consultas')
                ->leftJoin('users', 'users.id', '=', 'consultas.paciente_id')
                ->select('users.nome', 'data', 'hora')
                ->where('psicologo_id', $psiID[0]->psiId)
                ->get();

            return response(["schedule" => $schedule]);
        } catch (Exception $th) {
            return response(["error" => "Erro inesperado!"]);
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
