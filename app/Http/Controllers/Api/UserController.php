<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ContactosController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PsicologoController;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($this->validating($request)) {
            try {
                $user = User::create([
                    'nome' => $request->nome . ' ' . $request->apelido,
                    'email' => $request->email,
                    'acesso' => $this->getAcesso($request),
                    'password' => Hash::make($request->password),
                    'novo' => $request->paciente == true ? 0 : 1
                ]);

                if ($this->insertUserOrPsi($request, $user->id) == 1) {
                    return response(['success' =>  'Registo feito com sucesso']);
                } else {
                    return response(['error' => 'Ocorreu um erro no registo!']);
                }
            } catch (Exception $th) {
                return response(['error' => 'Erro inesperado!']);
            }
        } else {
            return response(['warning' => 'Preencha os dados correctamente!']);
        }
    }


    function insertUserOrPsi($request, $user_id)
    {
        if ($request->paciente) {
            $paciente = new PacienteController();
            return $paciente->store($request, $user_id);
        } else {
            $psicologo = new PsicologoController();
            return $psicologo->store($user_id, $request);
        }
    }


    function getAcesso($request)
    {
        if (gettype($request->paciente) == 'boolean') {
            return $request->paciente == true ? 'paciente' : 'psicologo';
        } else {
            return 'admin';
        }
    }

    function getPacientesCount()
    {
        try {
            $count = DB::table('users')
                ->select(array('id', DB::raw('COUNT(id) as total')))
                ->where('acesso', 'paciente')
                ->get();
                
            return $count;
        } catch (Exception $th) {
            return 0;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    function validating($request)
    {
        try {
            $request->validate([
                'email' => 'email|max:50|'/*unique:users,email,*/,
                'nome' => 'string|required',
                'apelido' => 'string|required',
                'password' => 'exclude_if:paciente,false|required',
                'paciente' => 'required',
            ]);
            return true;
        } catch (\Illuminate\Validation\ValidationException $th) {
            return false;
        }
    }
}
