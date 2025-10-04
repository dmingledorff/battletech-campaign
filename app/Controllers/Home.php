<?php namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Shield\Authentication\Authenticators\Session;

class Home extends BaseController
{
    public function index()
    {
        // Get Shield's authentication service
        $auth = service('auth');

        if ($auth->loggedIn()) {
            // ✅ User is logged in → send them to the dashboard (or wherever)
            return redirect()->to('/dashboard');
        }

        // 🚪 Not logged in → send to login
        return redirect()->to('/login');
    }
}
