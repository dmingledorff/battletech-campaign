<?php namespace App\Models;

use CodeIgniter\Model;

class StarSystemModel extends Model
{
    protected $table = 'star_systems';
    protected $primaryKey = 'system_id';
    protected $allowedFields = ['name'];
}
