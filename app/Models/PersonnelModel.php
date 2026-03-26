<?php namespace App\Models;

use CodeIgniter\Model;

class PersonnelModel extends Model
{
    protected $table      = 'personnel';
    protected $primaryKey = 'personnel_id';
    protected $allowedFields = [
        'first_name',
        'last_name',
        'rank_id',
        'status',
        'gender',
        'callsign',
        'mos',
        'experience',
        'missions',
        'morale'
    ];

    protected $returnType = 'array';

    /**
     * Base query with rank join
     */
    protected function baseSelect()
    {
        return $this->db->table($this->table . ' p')
            ->select('
                p.*,
                r.full_name AS rank_full,
                r.abbreviation AS rank_abbr,
                r.grade,
                loc.name AS location_name,
                loc.location_id AS location_id,
                pl.name AS planet_name
            ')
            ->join('ranks r', 'p.rank_id = r.id', 'left')
            ->join('locations loc', 'loc.location_id = p.location_id', 'left')
            ->join('planets pl', 'pl.planet_id = loc.planet_id', 'left');
    }

    /**
     * Override find() to include rank data
     */
    public function find($id = null)
    {
        return $this->baseSelect()
            ->where('p.personnel_id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Override findAll() to include rank data
     */
    public function findAll(?int $limit = null, int $offset = 0)
    {
        $builder = $this->baseSelect();
        if ($limit !== null) {
            $builder->limit($limit, $offset);
        }

        return $builder
            ->orderBy('r.grade', 'DESC')
            ->orderBy('p.last_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Generic where() finder with rank data
     */
    public function findWhere(array $where, int $limit = 0, int $offset = 0)
    {
        $builder = $this->baseSelect()->where($where);
        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }
        return $builder
            ->orderBy('r.grade', 'DESC')
            ->get()
            ->getResultArray();
    }

    // Units this person is assigned to
    public function getAssignments($personnelId)
    {
        return $this->db->table('personnel_assignments pa')
            ->select('u.unit_id, u.name, u.unit_type, u.nickname')
            ->join('units u', 'u.unit_id = pa.unit_id')
            ->where('pa.personnel_id', $personnelId)
            ->get()
            ->getResultArray();
    }

    // Equipment this person is crewing
    public function getEquipmentAssignments($personnelId)
    {
        return $this->db->table('personnel_equipment pe')
            ->select('
                e.equipment_id,
                e.serial_number,
                e.equipment_status,
                e.damage_percentage,
                c.name as chassis_name,
                c.type as chassis_type,
                c.variant as chassis_variant,
                c.weight_class,
                pe.role
            ')
            ->join('equipment e', 'e.equipment_id = pe.equipment_id')
            ->join('chassis c', 'c.chassis_id = e.chassis_id')
            ->where('pe.personnel_id', $personnelId)
            ->where('pe.is_active', true)
            ->get()
            ->getResultArray();
    }

    // Current active assignment only
    public function getCurrentAssignment(int $personnelId): ?array
    {
        $row = $this->db->table('personnel_assignments pa')
            ->select('u.unit_id, u.name, u.unit_type, u.nickname, pa.date_assigned')
            ->join('units u', 'u.unit_id = pa.unit_id')
            ->where('pa.personnel_id', $personnelId)
            ->where('pa.date_released IS NULL', null, false)
            ->orderBy('pa.date_assigned', 'DESC')
            ->get(1)
            ->getRowArray();

        return $row ?: null;
    }

    // Full assignment history
    public function getAssignmentHistory(int $personnelId): array
    {
        return $this->db->table('personnel_assignments pa')
            ->select('u.unit_id, u.name, u.unit_type, u.nickname, pa.date_assigned, pa.date_released')
            ->join('units u', 'u.unit_id = pa.unit_id')
            ->where('pa.personnel_id', $personnelId)
            ->orderBy('pa.date_assigned', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getRoster(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;

        $builder = $this->db->table('personnel p')
            ->select('
                p.personnel_id,
                p.first_name,
                p.last_name,
                p.status,
                p.mos,
                p.morale,
                r.abbreviation AS rank_abbr,
                r.grade,
                u.unit_id AS unit_id,
                u.name AS unit_name,
                loc.location_id,
                loc.name AS location_name,
                pl.name AS planet_name
            ')
            ->join('ranks r', 'r.id = p.rank_id', 'left')
            ->join('personnel_assignments pa', 
                'pa.personnel_id = p.personnel_id AND pa.date_released IS NULL', 'left')
            ->join('units u', 'u.unit_id = pa.unit_id', 'left')
            ->join('locations loc', 'loc.location_id = p.location_id', 'left')
            ->join('planets pl', 'pl.planet_id = loc.planet_id', 'left');

        // Unassigned filter
        if (!empty($filters['unassigned'])) {
            $builder->where('pa.unit_id IS NULL', null, false);
        }

        // Unit filter — collect all descendant unit IDs
        if (!empty($filters['unit_id'])) {
            $unitIds = $this->getDescendantUnitIds((int)$filters['unit_id']);
            $builder->whereIn('pa.unit_id', $unitIds);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $builder->where('p.status', $filters['status']);
        }

        // MOS filter
        if (!empty($filters['mos'])) {
            $builder->where('p.mos', $filters['mos']);
        }

        // Location filter
        if (!empty($filters['location_id'])) {
            $builder->where('p.location_id', (int)$filters['location_id']);
        } elseif (!empty($filters['_planet'])) {
            // No specific location selected — filter by all locations on that planet
            $locationIds = $this->db->table('locations')
                ->select('location_id')
                ->where('planet_id', (int)$filters['_planet'])
                ->get()
                ->getResultArray();
            $ids = array_column($locationIds, 'location_id');
            if (!empty($ids)) {
                $builder->whereIn('p.location_id', $ids);
            }
        }

        $builder->orderBy('r.grade', 'DESC')
                ->orderBy('p.last_name', 'ASC');

        // Get total count before pagination
        $total = $builder->countAllResults(false);

        // Apply pagination
        $rows = $builder->limit($perPage, $offset)->get()->getResultArray();

        return [
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'last_page'  => (int)ceil($total / $perPage),
        ];
    }

    private function getDescendantUnitIds(int $unitId): array
    {
        $ids = [$unitId];
        $children = $this->db->table('units')
            ->select('unit_id')
            ->where('parent_unit_id', $unitId)
            ->get()->getResultArray();

        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getDescendantUnitIds($child['unit_id']));
        }

        return $ids;
    }

    public function getUnitRosterForMission(int $unitId): array
    {
        return $this->db->table('personnel p')
            ->select('
                p.last_name,
                r.abbreviation AS rank_abbr,
                p.mos,
                c.variant
            ')
            ->join('ranks r', 'r.id = p.rank_id', 'left')
            ->join('personnel_assignments pa',
                'pa.personnel_id = p.personnel_id AND pa.date_released IS NULL', 'left')
            ->join('personnel_equipment pe',
                'pe.personnel_id = p.personnel_id AND pe.is_active = 1', 'left')
            ->join('equipment e', 'e.equipment_id = pe.equipment_id', 'left')
            ->join('chassis c', 'c.chassis_id = e.chassis_id', 'left')
            ->where('pa.unit_id', $unitId)
            ->orderBy('r.grade', 'DESC')
            ->get()
            ->getResultArray();
    }
}
