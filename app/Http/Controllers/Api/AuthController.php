<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MailController;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

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
        }

        return $this->respondWithToken($token);
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
            'expires_in' => auth('api')->factory()->getTTL() * 60
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
                    return response(['success' => 'Foi enviado um codigo no seu email para a reposicao da senha!']);
                } else {
                    return response(['error' => 'Erro inesperado']);
                }
            } else {
                return response(['error' => 'Email invalido']);
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

            if (strlen($request->password) < 8) {
                return response(['warning' => 'A sua e senha muito curta!']);
            } else {
                try {
                    $user = auth('api')->user();

                    User::where('id', $user->id)
                        ->update([
                            'password' => Hash::make($request->password),
                            'novo' => '0'
                        ]);

                    return response(['success' => 'Atualizacao feita com sucesso!']);
                } catch (Exception $th) {
                    return response(['error' => 'Erro inesperado']);
                }
            }
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response(['error' => 'Preencha todos os campos!']);
        }
    }
}
