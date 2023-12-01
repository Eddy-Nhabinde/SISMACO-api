<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Http\Controllers\Utils\PsicologosUtils;
use App\Models\Psicologo;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Email ou password invalido!'], 401);
        } else {
            $user = auth('api')->user();
            if ($user->acesso == 'psicologo') {
                $estado = DB::table('psicologos')
                    ->select('estado')
                    ->where('user_id', $user->id)
                    ->get();
                if ($estado[0]->estado != 0) return $this->respondWithToken($token);
                else return response()->json(["warning" => "Acesso negado!"]);
            } else {
                return $this->respondWithToken($token);
            }
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth('api')->user();
        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 1
        ]);
    }

    function requestPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $email = User::select('email', 'id')
                ->where('email', '=', $request->email)
                ->get();

            if (sizeof($email) > 0) {
                $pass = mt_rand(1000000000, 9999999999);

                $mail = new MailController();

                if ($mail->sendPassword($email[0]->email, $pass, null, null) == 1) {
                    User::where('id', $email[0]->id)
                        ->update([
                            'password' => Hash::make($pass),
                            'novo' => '1'
                        ]);
                    return response(['success' => 'Foi enviado um codigo no seu email']);
                } else {
                    return response(['error' => 'Ocorreu um Erro Inesperado']);
                }
            } else {
                return response(['' => 'Email invalido']);
            }
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response(['error' => 'Email invalido']);
        }
    }

    function passwordUpdate(Request $request)
    {
        try {
            $request->validate([
                'password' => 'required',
            ]);

            // if (strlen($request->senha) < 8) {
            //     return response(['warning' => 'A sua e senha muito curta!']);
            // } else {
                try {
                    $user = auth('api')->user();

                    User::where('id', $user->id)
                        ->update([
                            'password' => Hash::make($request->password
                        ),
                            'novo' => '0'
                        ]);

                    $utis = new PsicologosUtils();

                    if ($user->acesso == 'psicologo') {
                        Psicologo::where('user_id', $utis->getPsicologId($user->id))
                            ->update([
                                'estado' => 1
                            ]);
                    }

                    return response(['success' => 'Atualizacao feita com sucesso! Agora faça o login']);
                } catch (Exception $th) {
                    return response(['error' => 'Ocorreu um Erro Inesperado']);
                }
            // }
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response(['error' => 'Preencha todos os campos!']);
        }
    }
}
