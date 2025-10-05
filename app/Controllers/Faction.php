<?php namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Faction extends BaseController
{
    public function select()
    {
        $factions = model('FactionModel')->findAll();

        return $this->render('faction/select', [
            'factions' => $factions,
        ]);
    }

    public function save()
    {
        $user = auth()->user();
        $factionId = $this->request->getPost('faction_id');

        if ($user && $factionId) {
            // Update via model, not entity
            $userModel = new UserModel();
            $userModel->update($user->id, ['faction_id' => $factionId]);

            $updatedUser = $userModel->find($user->id);
            auth()->setUser($updatedUser);
        }

        return redirect()->to('/dashboard');
    }
}
