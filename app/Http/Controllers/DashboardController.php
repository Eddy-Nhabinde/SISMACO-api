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
    private $psicologoId = null;
    private $psiUtils = null;
    private $acesso = 0;
    private $contacts = null;

    public function __construct()
    {
        $userUtils = new Common();
        $this->psiUtils = new PsicologosUtils();
        $this->acesso = $userUtils->getAcesso();
    }

    function getDashBoardData(Request $request)
    {
        try {
            $userUtils = new Common();

            if ($this->acesso == 'admin' && $request->psicologo_id != 'null') {

                $this->psicologoId = $request->psicologo_id;
                $psiUtils = new PsicologosUtils();
                $this->contacts =  $userUtils->getContacts($psiUtils->getUserId($request->psicologo_id));
            } else if ($this->acesso == 'psicologo')
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
                    'allAppointments' => $this->getAppointmentCount(null),
                    'thisYearAppointments' => $this->getAppointmentCount($yearly),
                    'thisMonthAppointments' => $this->getAppointmentCount($monthly),
                    "users" => $user->getPacientesCount(),
                    "thisMonth" => $utils->organizeDataByMonth($this->getChartData($monthly, $monthlySelect, 'day'), 'month'),
                    "thisYear" => $utils->organizeDataByMonth($this->getChartData($yearly, $yearlySelect, 'month'), 'year'),
                    "contactos" => $this->contacts
                ]]);
            }

            return response(["dashData" => [
                'allAppointments' => $this->getAppointmentCount(null),
                'thisYearAppointments' => $this->getAppointmentCount($yearly),
                'thisMonthAppointments' => $this->getAppointmentCount($monthly),
                "thisMonth" => $utils->organizeDataByMonth($this->getChartData($monthly, $monthlySelect, 'day'), 'month'),
                "thisYear" => $utils->organizeDataByMonth($this->getChartData($yearly, $yearlySelect, 'month'), 'year'),
            ]]);
        } catch (Exception $th) {
            return response(["error" => "Ocorreu um Erro Inesperado!" . $th]);
        }
    }

    function getAppointmentCount($where)
    {
        $data = DB::table('consultas')
            ->join('estados', 'estados.id', '=', 'consultas.estado_id')
            ->when($this->acesso, function ($query) {
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

    function getChartData($where, $select, $groupBy)
    {
        $data =  DB::table('consultas')
            ->join('estados', 'estados.id', '=', 'consultas.estado_id')
            ->when($this->acesso, function ($query) {
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

            ->when($this->psicologoId, function ($query, $id) {
                if ($this->psicologoId != null) {
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
