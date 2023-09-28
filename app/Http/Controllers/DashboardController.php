<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Utils\Common;
use App\Http\Controllers\Utils\ConsultasUtils;
use App\Http\Controllers\Utils\PsicologosUtils;
use Exception;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private $userId = 0;
    private $acesso = 0;
    private $psicologoUtils = null;

    public function __construct()
    {
        $user = new Common();
        $this->psicologoUtils = new PsicologosUtils();
        $this->userId = $user->getUserId();
        $this->acesso = $user->getAcesso();
    }

    function getDashBoardData($year = null, $id = null)
    {
        try {
            $year == null && $year = date("Y");

            $yearlySelect = [DB::raw('count(consultas.id) as `data`'),  DB::raw('MONTH(consultas.created_at) month')];
            $monthlySelect = [DB::raw('count(consultas.id) as `data`'),  DB::raw('DAY(consultas.created_at) day')];

            $yearly = [[DB::raw('YEAR(consultas.created_at)'), '=', $year]];
            $monthly = [['consultas.created_at', '>', now()->subDays(30)->endOfDay()]];

            $utils = new ConsultasUtils();

            if ($this->acesso != 'psicologo') {
                $user = new UserController();
                return response(["dashData" => [
                    'allAppointments' => $this->getAppointmentCount($id, null),
                    'thisYearAppointments' => $this->getAppointmentCount($id, $yearly),
                    'thisMonthAppointments' => $this->getAppointmentCount($id, $monthly),
                    "users" => $user->getPacientesCount(),
                    "thisMonth" => $utils->organizeDataByMonth($this->getChartData($id, $monthly, $monthlySelect, 'day'), 'day'),
                    "thisYear" => $utils->organizeDataByMonth($this->getChartData($id, $yearly, $yearlySelect, 'month'), 'month')
                ]]);
            }

            return response(["dashData" => [
                'allAppointments' => $this->getAppointmentCount($id, null),
                'thisYearAppointments' => $this->getAppointmentCount($id, $yearly),
                'thisMonthAppointments' => $this->getAppointmentCount($id, $monthly),
                "thisMonth" => $utils->organizeDataByMonth($this->getChartData($id, $monthly, $monthlySelect, 'day'), 'month'),
                "thisYear" => $utils->organizeDataByMonth($this->getChartData($id, $yearly, $yearlySelect, 'month'), 'year')
            ]]);
        } catch (Exception $th) {
            return response(["error" => "Erro inesperado!" . $th]);
        }
    }

    function getAppointmentCount($id, $where)
    {
        $data = DB::table('consultas')
            ->join('estados', 'estados.id', '=', 'consultas.estado_id')
            ->when($this->acesso || $id, function ($query, $id) {
                if ($this->acesso == 'psicologo' || $id != null) {
                    return $query->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id');
                }
            })
            ->select('estados.nome as estado', DB::raw('COUNT(estado_id) as total'))
            ->when($this->acesso, function ($query) {
                if ($this->acesso == 'psicologo') {
                    return $query->where('psicologos.id', $this->psicologoUtils->getPsicologId($this->userId));
                }
            })
            ->when($id, function ($query, $id) {
                if ($id != null) {
                    return $query->where('psicologos.id', $id);
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
                if ($this->acesso == 'psicologo' || $id != null) {
                    return $query->join('psicologos', 'psicologos.id', '=', 'consultas.psicologo_id');
                }
            })
            ->select($select)
            ->when($this->acesso, function ($query) {
                if ($this->acesso == 'psicologo') {
                    return $query->where('psicologos.id', $this->psicologoUtils->getPsicologId($this->userId));
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
