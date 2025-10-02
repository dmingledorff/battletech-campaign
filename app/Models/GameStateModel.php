<?php namespace App\Models;

use CodeIgniter\Model;

class GameStateModel extends Model
{
    protected $table = 'game_state';
    protected $primaryKey = 'id';
    protected $allowedFields = ['property_name', 'property_value'];

    public function getProperty(string $key, $default = null) {
        $row = $this->where('property_name', $key)->first();
        return $row['property_value'] ?? $default;
    }

    public function setProperty(string $key, $value): void {
        $row = $this->where('property_name', $key)->first();

        if ($row) {
            $this->update($row['id'], ['property_value' => $value]);
        } else {
            $this->insert(['property_name' => $key, 'property_value' => $value]);
        }
    }

    private function updateOrInsert(array $where, array $data): void {
        $existing = $this->where($where)->first();
        if ($existing) {
            $this->where('id', $existing['id'])->set($data)->update();
        } else {
            $this->insert(array_merge($where, $data));
        }
    }

    public function getCurrentDate(): \DateTime {
        $row = $this->first();
        return $row ? new \DateTime($row['current_date']) : new \DateTime('3025-01-01');
    }

    public function advanceDate(int $days = 1): void
    {
        $row = $this->first();
        if (!$row) {
            $this->insert([
                'current_date' => (new \DateTime('3025-01-01'))->modify("+{$days} days")->format('Y-m-d'),
                'last_tick'    => date('Y-m-d H:i:s'),
                'tick_count'   => 1,
            ]);
            return;
        }

        $date = new \DateTime($row['current_date']);
        $date->modify("+{$days} days");

        $this->update($row['id'], [
            'current_date' => $date->format('Y-m-d'),
            'last_tick'    => date('Y-m-d H:i:s'),
            'tick_count'   => ($row['tick_count'] ?? 0) + 1,
        ]);
    }
}
