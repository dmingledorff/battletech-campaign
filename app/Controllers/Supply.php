<?php
namespace App\Controllers;
use App\Models\UnitModel;
class Supply extends BaseController {
  public function index(){
    $um = new UnitModel();
    $summary = $um->getSummaryAll();
    return view('layout/header')
         . view('units/index',['summary'=>$summary])
         . view('layout/footer');
  }
}