<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EspecialidadeController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\ProblemaController;
use App\Http\Controllers\PsicologoController;
use App\Http\Controllers\Utils\ConsultasUtils;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building yokur API!
|
*/

route::post('newPatient', PacienteController::class . '@store');

route::post('newPsychologist', PsicologoController::class . '@store');

route::put('deactivate/{id}/{estado}', PsicologoController::class . '@Deactivate');

route::put('updatePsycho', PsicologoController::class . '@update');

route::get('getPsychoNames', PsicologoController::class . '@getPsychoNames');

route::post('newAppointment', ConsultaController::class . '@novaConsulta');

route::get('getAppointments/{estado}', ConsultaController::class . '@getAppointments');

route::get('getPacienteAppointments', ConsultaController::class . '@getPacienteAppointments');

route::put('closeAppointment/{id}', ConsultaController::class . '@CloseAppointment');

route::put('cancelAppointment/{id}', ConsultaController::class . '@cancelAppointment');

route::get('getDashBoardData', DashboardController::class . '@getDashBoardData');

route::post('Reschedule', ConsultaController::class . '@Reschedule');

route::get('historico/{paciente_id}', PacienteController::class . '@historico');

route::get('getPsychologist', PsicologoController::class . '@getPsicologos');

route::get('getSchedule', PsicologoController::class . '@getSchedule');

route::put('AlterarEstado/{id}/{estadoId}', PsicologoController::class . '@AlterarEstado');

route::get('getPsiDetails/{id}', PsicologoController::class . '@getPsychologistDetails');

route::get('getEspecialidade', EspecialidadeController::class . '@getEspecialidade');

route::get('getProblems', ProblemaController::class . '@getProblems');

route::get('getBusySchedules', ConsultasUtils::class . '@getBusySchedules');

route::group(['middleware' => ['apijwt']], function () {
});

Route::post('passwordRequest', AuthController::class . '@requestPassword');

Route::post('login', AuthController::class . '@login');

Route::post('passwordUpdate', AuthController::class . '@passwordUpdate');

Route::post('refresh', AuthController::class . '@refresh');

Route::post('me', AuthController::class . '@me');
