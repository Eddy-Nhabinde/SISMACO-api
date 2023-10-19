<?php

namespace App\Http\Requests;

use App\Http\Controllers\Utils\Common;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ]));
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */

    public function rules()
    {
        return [
            "nome" => 'required|string',
            "apelido" => 'required|string',
            "email" => 'required|email',
            "especialidade" => 'required|integer',
            "disponibilidade" => 'required',
            "contacto1" => "required|string|min:9|max:9",
        ];
    }
}
