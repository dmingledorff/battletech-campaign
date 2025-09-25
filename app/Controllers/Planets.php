<?php
namespace App\Controllers;
use App\Models\PlanetsModel;
class Planets extends BaseController {
  public function index(){
    $pm = new PlanetsModel();
    $rows = $pm->findAll();
    return view('layout/header')
         . view('dashboard/plainlist',['title'=>'Planets','rows'=>$rows,'field'=>'name'])
         . view('layout/footer');
  }
}