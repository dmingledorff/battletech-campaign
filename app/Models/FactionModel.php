<?php namespace App\Models;

use CodeIgniter\Model;

class FactionModel extends Model
{
    protected $table      = 'factions';
    protected $primaryKey = 'faction_id';
    protected $allowedFields = [
        'name',
        'description',
        'emblem_path',
        'color'
    ];
    protected $returnType = 'array';
}
