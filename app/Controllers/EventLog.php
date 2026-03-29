<?php namespace App\Controllers;

use App\Models\EventLogModel;

class EventLog extends BaseController
{
    public function index()
    {
        $factionId = $this->currentFaction['faction_id'] ?? null;
        $model     = new EventLogModel();

        $logTypes  = $this->getEnumValues('event_log', 'log_type');
        $severities = $this->getEnumValues('event_log', 'severity');

        $filters = [
            'log_type'  => $this->request->getGet('log_type'),
            'severity'  => $this->request->getGet('severity'),
            'date_from' => $this->request->getGet('date_from'),
            'date_to'   => $this->request->getGet('date_to'),
            'page'      => (int)($this->request->getGet('page') ?? 1),
        ];

        $result = $model->getFiltered(
            $factionId,
            $filters['log_type']  ?: null,
            $filters['date_from'] ?: null,
            $filters['date_to']   ?: null,
            $filters['severity']  ?: null,
            $filters['page']
        );

        return $this->render('eventlog/index', [
            'result'     => $result,
            'filters'    => $filters,
            'logTypes'   => $logTypes,
            'severities' => $severities,
        ]);
    }
}