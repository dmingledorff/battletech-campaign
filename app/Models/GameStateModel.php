<?php namespace App\Models;

use CodeIgniter\Model;

class GameStateModel extends Model
{
    protected $table = 'game_state';
    protected $primaryKey = 'id';
    protected $allowedFields = ['property_name', 'property_value'];

    public function getProperty(string $name): ?string {
        $row = $this->where('property_name', $name)->first();
        return $row['property_value'] ?? null;
    }

    public function setProperty(string $name, string $value): void {
        $this->updateOrInsert(['property_name' => $name], ['property_value' => $value]);
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
