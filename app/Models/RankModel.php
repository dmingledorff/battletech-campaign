<?php namespace App\Models;

use CodeIgniter\Model;

class RankModel extends Model
{
    protected $table      = 'ranks';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'faction',
        'full_name',
        'abbreviation',
        'grade'
    ];
    protected $returnType = 'array';

    /**
     * Get rank ID by full name (e.g., "Major")
     */
    public function getRankIdByName(string $rankName, string $faction = 'Davion'): ?int
    {
        $row = $this->select('id')
            ->where('full_name', $rankName)
            ->where('faction', $faction)
            ->get()
            ->getRowArray();

        return $row['id'] ?? null;
    }

    /**
     * Get rank row by ID
     */
    public function getRankById(int $id): ?array
    {
        return $this->where('id', $id)->first();
    }

    /**
     * Get all ranks for a faction (ordered by grade)
     */
    public function getRanksForFaction(string $faction): array
    {
        return $this->where('faction', $faction)
            ->orderBy('grade', 'ASC')
            ->findAll();
    }

    /**
     * Promote to the next higher rank (by grade)
     */
    public function getNextRankId(int $currentRankId): ?int
    {
        $current = $this->getRankById($currentRankId);
        if (!$current) {
            return null;
        }

        $next = $this->where('faction', $current['faction'])
            ->where('grade >', $current['grade'])
            ->orderBy('grade', 'ASC')
            ->first();

        return $next['id'] ?? null;
    }

    /**
     * Demote to the next lower rank (by grade)
     */
    public function getPreviousRankId(int $currentRankId): ?int
    {
        $current = $this->getRankById($currentRankId);
        if (!$current) {
            return null;
        }

        $prev = $this->where('faction', $current['faction'])
            ->where('grade <', $current['grade'])
            ->orderBy('grade', 'DESC')
            ->first();

        return $prev['id'] ?? null;
    }
}
