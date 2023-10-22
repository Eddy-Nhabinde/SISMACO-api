<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PsicologoController;
use App\Http\Controllers\Utils\Common;
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
    public function store($request)
    {
        try {
            $password = $this->getOrGenetatePAssword($request);

            $user = User::create([
                'nome' => $request->nome . ' ' . $request->apelido,
                'email' => $request->email,
                'acesso' => $request->acesso,
                'password' => Hash::make($password),
                'novo' => $request->paciente == true ? 0 : 1
            ]);

            return ["password" => $password, "id" => $user->id];
        } catch (Exception $th) {
            return false;
        }
    }

    function getOrGenetatePAssword($request)
    {
        if (isset($request->password) && $request->paciente == true) {
            return $request->password;
        } else {
            $utils = new Common();
            return $utils->returnRandomString();
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
