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
                r.grade
            ')
            ->join('ranks r', 'p.rank_id = r.id', 'left');
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
                c.name as chassis_name,
                c.type as chassis_type,
                c.weight_class,
                pe.role
            ')
            ->join('equipment e', 'e.equipment_id = pe.equipment_id')
            ->join('chassis c', 'c.chassis_id = e.chassis_id')
            ->where('pe.personnel_id', $personnelId)
            ->get()
            ->getResultArray();
    }
}
