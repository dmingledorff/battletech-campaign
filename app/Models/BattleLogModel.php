<?php

namespace App\Models;

use CodeIgniter\Model;

class BattleLogModel extends Model
{
    protected $table      = 'battle_log';
    protected $primaryKey = 'log_id';
    protected $allowedFields = [
        'mission_id', 'game_date', 'game_hour', 'combat_phase', 'combat_round',
        'log_type', 'attacker_id', 'target_id', 'damage_dealt', 'description',
    ];

    public function record(
        int    $missionId,
        string $gameDate,
        int    $gameHour,
        string $phase,
        int    $round,
        string $logType,
        string $description,
        ?int   $attackerId  = null,
        ?int   $targetId    = null,
        ?float $damageDealt = null
    ): void {
        $this->insert([
            'mission_id'   => $missionId,
            'game_date'    => $gameDate,
            'game_hour'    => $gameHour,
            'combat_phase' => $phase,
            'combat_round' => $round,
            'log_type'     => $logType,
            'description'  => $description,
            'attacker_id'  => $attackerId,
            'target_id'    => $targetId,
            'damage_dealt' => $damageDealt,
        ]);
    }

    public function getForMission(int $missionId): array
    {
        return $this->db->table('battle_log bl')
            ->select('bl.*,
                ca.serial_number  AS attacker_serial,
                cca.name          AS attacker_chassis,
                cca.variant       AS attacker_variant,
                ct.serial_number  AS target_serial,
                cct.name          AS target_chassis,
                cct.variant       AS target_variant')
            ->join('equipment ca',  'ca.equipment_id = bl.attacker_id', 'left')
            ->join('chassis cca',   'cca.chassis_id = ca.chassis_id',   'left')
            ->join('equipment ct',  'ct.equipment_id = bl.target_id',   'left')
            ->join('chassis cct',   'cct.chassis_id = ct.chassis_id',   'left')
            ->where('bl.mission_id', $missionId)
            ->orderBy('bl.log_id', 'ASC')
            ->get()->getResultArray();
    }

    public function getSummary(int $missionId): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(CASE WHEN log_type = 'Attack'    THEN 1 END) AS total_attacks,
                COUNT(CASE WHEN log_type = 'Destroyed' THEN 1 END) AS units_destroyed,
                COUNT(CASE WHEN log_type = 'Crippled'  THEN 1 END) AS units_crippled,
                COUNT(CASE WHEN log_type = 'Ejection'  THEN 1 END) AS pilots_ejected,
                COUNT(CASE WHEN log_type = 'Retreat'   THEN 1 END) AS units_retreated,
                COALESCE(SUM(CASE WHEN log_type = 'Attack' THEN damage_dealt ELSE 0 END), 0) AS total_damage,
                MAX(combat_round) AS total_rounds
            FROM battle_log
            WHERE mission_id = {$missionId}
        ")->getRowArray();

        return $row ?? [];
    }
}