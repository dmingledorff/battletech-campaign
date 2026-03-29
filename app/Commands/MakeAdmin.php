<?php namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MakeAdmin extends BaseCommand
{
    protected $group       = 'Game';
    protected $name        = 'game:makeadmin';
    protected $description = 'Grant admin group to a user by email.';

    public function run(array $params)
    {
        $email = $params[0] ?? CLI::prompt('User email');
        $provider = auth()->getProvider();
        $user = $provider->findByCredentials(['email' => $email]);

        if (!$user) {
            CLI::error("User not found: {$email}");
            return;
        }

        $user->addGroup('admin');
        CLI::write("Admin granted to {$email}", 'green');
    }
}