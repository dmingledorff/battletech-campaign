<?php namespace App\Entities;

use CodeIgniter\Shield\Entities\User as ShieldUser;

class User extends ShieldUser
{
    protected $casts = [
        'id'          => 'int',
        'faction_id'  => '?int',
    ];

    public function getFaction()
    {
        if (empty($this->attributes['faction_id'])) {
            return null;
        }

        return model('FactionModel')->find($this->attributes['faction_id']);
    }
}
