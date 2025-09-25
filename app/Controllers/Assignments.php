<?php
namespace App\Controllers;
use App\Models\PersonnelEquipmentModel;
class Assignments extends BaseController {
  public function index(){
    $pem = new PersonnelEquipmentModel();
    $rows = $pem->getAll();
    return view('layout/header')
         . view('assignments/index',['rows'=>$rows])
         . view('layout/footer');
  }
}