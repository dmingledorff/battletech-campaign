<?php

namespace App\Models;

use CodeIgniter\Model;

class EventLogModel extends Model
{
    protected $table      = 'event_log';
    protected $primaryKey = 'log_id';
    protected $allowedFields = [
        'faction_id',
        'game_date',
        'log_type',
        'severity',
        'title',
        'description',
        'unit_id',
        'mission_id',
        'location_id',
        'personnel_id',
    ];

    public function log(
        ?int    $factionId,
        string $gameDate,
        string $logType,
        string $title,
        string $description  = '',
        string $severity     = 'Info',
        ?int   $unitId       = null,
        ?int   $missionId    = null,
        ?int   $locationId   = null,
        ?int   $personnelId  = null
    ): int {
        $this->insert([
            'faction_id'   => $factionId,
            'game_date'    => $gameDate,
            'log_type'     => $logType,
            'severity'     => $severity,
            'title'        => $title,
            'description'  => $description ?: null,
            'unit_id'      => $unitId,
            'mission_id'   => $missionId,
            'location_id'  => $locationId,
            'personnel_id' => $personnelId,
        ]);
        return $this->db->insertID();
    }

    public function getForDashboard(int $factionId, string $gameDate, int $days = 7): array
    {
        $cutoff = (new \DateTime($gameDate))->modify("-{$days} days")->format('Y-m-d');

        return $this->db->table('event_log el')
            ->select('el.*,
            u.name AS unit_name,
            m.name AS mission_name,
            l.name AS location_name,
            CONCAT(r.abbreviation, \'. \', p.last_name) AS personnel_name')
            ->join('units u',     'u.unit_id = el.unit_id',           'left')
            ->join('missions m',  'm.mission_id = el.mission_id',     'left')
            ->join('locations l', 'l.location_id = el.location_id',   'left')
            ->join('personnel p', 'p.personnel_id = el.personnel_id', 'left')
            ->join('ranks r',     'r.id = p.rank_id',                 'left')
            ->groupStart()
            ->where('el.faction_id', $factionId)
            ->orWhere('el.faction_id IS NULL', null, false)
            ->groupEnd()
            ->where('el.game_date >=', $cutoff)
            ->orderBy('el.game_date', 'DESC')
            ->orderBy('el.log_id',    'DESC')
            ->get()->getResultArray();
    }

    public function getFiltered(
        int     $factionId,
        ?string $logType  = null,
        ?string $dateFrom = null,
        ?string $dateTo   = null,
        ?string $severity = null,
        int     $page     = 1,
        int     $perPage  = 50
    ): array {
        $builder = $this->db->table('event_log el')
            ->select('el.*,
            u.name AS unit_name,
            m.name AS mission_name,
            l.name AS location_name,
            CONCAT(r.abbreviation, \'. \', p.last_name) AS personnel_name')
            ->join('units u',     'u.unit_id = el.unit_id',           'left')
            ->join('missions m',  'm.mission_id = el.mission_id',     'left')
            ->join('locations l', 'l.location_id = el.location_id',   'left')
            ->join('personnel p', 'p.personnel_id = el.personnel_id', 'left')
            ->join('ranks r',     'r.id = p.rank_id',                 'left')
            ->groupStart()
            ->where('el.faction_id', $factionId)
            ->orWhere('el.faction_id IS NULL', null, false)
            ->groupEnd();

        if ($logType)  $builder->where('el.log_type',     $logType);
        if ($severity) $builder->where('el.severity',     $severity);
        if ($dateFrom) $builder->where('el.game_date >=', $dateFrom);
        if ($dateTo)   $builder->where('el.game_date <=', $dateTo);

        $total  = $builder->countAllResults(false);
        $offset = ($page - 1) * $perPage;
        $rows   = $builder
            ->orderBy('el.game_date', 'DESC')
            ->orderBy('el.log_id',    'DESC')
            ->limit($perPage, $offset)
            ->get()->getResultArray();

        return [
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    public function getForMission(int $missionId): array
    {
        return $this->db->table('event_log')
            ->where('mission_id', $missionId)
            ->orderBy('game_date', 'ASC')
            ->orderBy('log_id',    'ASC')
            ->get()->getResultArray();
    }
}
