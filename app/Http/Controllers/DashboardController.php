<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Utils\Common;
use App\Http\Controllers\Utils\ConsultasUtils;
use App\Http\Controllers\Utils\PsicologosUtils;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private $psicologoId = 0;
    private $psiUtils = null;
    private $acesso = 0;
    private $psicologoUtils = null;

    public function __construct()
    {
        $userUtils = new Common();
        $this->psiUtils = new PsicologosUtils();
        $this->psicologoUtils = new PsicologosUtils();
        $this->acesso = $userUtils->getAcesso();
    }

    function getDashBoardData(Request $request)
    {
        try {
            $userUtils = new Common();

            if ($this->acesso == 'admin' && $request->user_id != null)
                $this->psicologoId = $this->psiUtils->getPsicologId($request->user_id);
            else if ($this->acesso == 'psicologo')
                $this->psicologoId = $this->psiUtils->getPsicologId($userUtils->getUserId());

            $year = null;
            $request->year == null && $year = date("Y");

            $yearlySelect = [DB::raw('count(consultas.id) as `data`'),  DB::raw('MONTH(consultas.created_at) month')];
            $monthlySelect = [DB::raw('count(consultas.id) as `data`'),  DB::raw('DAY(consultas.created_at) day')];

            $yearly = [[DB::raw('YEAR(consultas.created_at)'), '=', $year]];
            $monthly = [['consultas.created_at', '>', now()->subDays(30)->endOfDay()]];

            $utils = new ConsultasUtils();

            if ($this->acesso != 'psicologo') {
                $user = new UserController();
                return response(["dashData" => [
                    'allAppointments' => $this->getAppointmentCount($request->user_id, null),
                    'thisYearAppointments' => $this->getAppointmentCount($request->user_id, $yearly),
                    'thisMonthAppointments' => $this->getAppointmentCount($request->user_id, $monthly),
                    "users" => $user->getPacientesCount(),
                    "thisMonth" => $utils->organizeDataByMonth($this->getChartData($request->user_id, $monthly, $monthlySelect, 'day'), 'month'),
                    "thisYear" => $utils->organizeDataByMonth($this->getChartData($request->user_id, $yearly, $yearlySelect, 'month'), 'year')
                ]]);
            }

            return response(["dashData" => [
                'allAppointments' => $this->getAppointmentCount($request->user_id, null),
                'thisYearAppointments' => $this->getAppointmentCount($request->user_id, $yearly),
                'thisMonthAppointments' => $this->getAppointmentCount($request->user_id, $monthly),
                "thisMonth" => $utils->organizeDataByMonth($this->getChartData($request->user_id, $monthly, $monthlySelect, 'day'), 'month'),
                "thisYear" => $utils->organizeDataByMonth($this->getChartData($request->user_id, $yearly, $yearlySelect, 'month'), 'year'),
                $this->psicologoId
            ]]);
        } catch (Exception $th) {
            return response(["error" => "Erro inesperado!" . $th]);
        }
    }

    function getAppointmentCount($id, $where)
    {
        $data = DB::table('consultas')
            ->join('estados', 'estados.id', '=', 'consultas.estado_id')
            ->when($this->acesso || $id, function ($query) {
                if ($this->psicologoId != null) {
                    return $query->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id');
                }
            })
            ->select('estados.nome as estado', DB::raw('COUNT(estado_id) as total'))
            ->when($this->psicologoId, function ($query) {
                if ($this->psicologoId != null) {
                    return $query->where('psicologos.id', $this->psicologoId);
                }
            })
            ->when($where, function ($query, $where) {
                if ($where != null) {
                    return $query->where($where);
                }
            })
            ->groupBy('estado')
            ->groupBy('estados.nome')
            ->get();

        return $data;
    }

    function getChartData($id, $where, $select, $groupBy)
    {
        $data =  DB::table('consultas')
            ->join('estados', 'estados.id', '=', 'consultas.estado_id')
            ->when($this->acesso || $id, function ($query, $id) {
                if ($this->psicologoId != null) {
                    return $query->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id');
                }
            })
            ->select($select)
            ->when($this->acesso, function ($query) {
                if ($this->psicologoId != null) {
                    return $query->where('psicologos.id', $this->psicologoId);
                }
            })

            ->when($id, function ($query, $id) {
                if ($id != null) {
                    return $query->where('psicologos.id', $id);
                }
            })
            ->where($where)
            ->groupby($groupBy)
            ->get();

        // dd($data,$this->acesso);
        return $data;
    }
}
