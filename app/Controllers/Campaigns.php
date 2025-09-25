<?php
namespace App\Controllers;
use App\Models\CampaignsModel;
class Campaigns extends BaseController {
  public function index(){
    $cm = new CampaignsModel();
    $rows = $cm->findAll();
    return view('layout/header')
         . view('dashboard/plainlist',['title'=>'Campaigns','rows'=>$rows,'field'=>'name'])
         . view('layout/footer');
  }
}