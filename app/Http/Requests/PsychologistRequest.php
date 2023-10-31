<?php

namespace App\Http\Requests;

use App\Http\Controllers\Utils\Common;
use App\Http\Controllers\Utils\PsicologosUtils;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class PsychologistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = new Common();
        return $user->getAcesso() == 'admin';
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            "validation" => true,
            'warning' => $validator->errors()
        ]));
    }
    /**
     * Get the validation rules that apply to the request.
     * 
     * required|email|unique:users,email,' . $request->user_id, means:
     * 
     * the emails is still required and must be unique but ignore emails with
     * user id in the request
     *
     * @return array<string, mixed>
     */

    public function rules(Request $request)
    {
        return [
            "nome" => 'required|string',
            "apelido" => 'required|string',
            "email" => 'required|email|unique:users,email,' . $request->user_id,
            "especialidade" => 'required|integer',
            "disponibilidade" => 'required',
            "contacto1" => "required|string|min:9|max:13",
        ];
    }
}
