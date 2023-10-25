<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "paciente" => 'required|boolean',
            "nome" => 'required|string',
            "apelido" => 'required|string',
            "email" => 'required|email|unique:users,email',
            "estadoCivil" => 'required|string',
            "ocupacao" => 'required|string',
            "dataNasc" => 'required|date|before:-17 years',
            "sexo" => 'required|string',
            "contacto1" => "required|string|min:9|max:9",
            "password" => "required|string",
            "ConfPass" => "required|string"
        ];
    }
}
