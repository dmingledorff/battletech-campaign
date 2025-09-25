<?php
namespace App\Controllers;

class PersonnelEquipment extends BaseController {
    public function index() {
        return view('layout/header')
            . view('personnelequipment/index')
            . view('layout/footer');
    }
}