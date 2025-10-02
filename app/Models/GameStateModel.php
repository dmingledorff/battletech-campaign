<?php namespace App\Models;

use CodeIgniter\Model;

class GameStateModel extends Model
{
    protected $table = 'game_state';
    protected $primaryKey = 'id';
    protected $allowedFields = ['property_name', 'property_value'];

    /**
    * Get all game state properties as key => value array
    */
    public function getAllProperties(): array {
        $rows = $this->findAll();
        $result = [];

        foreach ($rows as $row) {
            $result[$row['property_name']] = $row['property_value'];
        }

        return $result;
    }

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
}
