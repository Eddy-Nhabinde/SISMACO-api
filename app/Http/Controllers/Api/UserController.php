<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ContactosController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PsicologoController;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
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
                $response = null;
                $user = User::create([
                    'nome' => $request->nome . ' ' . $request->apelido,
                    'email' => $request->email,
                    'acesso' => $request->paciente == true ? 'paciente' : 'psicologo',
                    'password' => Hash::make($request->senha)
                ]);

                if ($request->paciente) {
                    $paciente = new PacienteController();
                    $response = $paciente->store($request, $user->id);
                } else {
                    $psicologo = new PsicologoController();
                    $response = $psicologo->store($user->id, $request);
                }

                if ($response == 1) {
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


    function insertUser()
    {
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
                'senha' => 'required',
                'paciente' => 'required|boolean',
            ]);
            return true;
        } catch (\Illuminate\Validation\ValidationException $th) {
            return false;
        }
    }
}
