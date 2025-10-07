<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/dashboard', 'Dashboard::index');
$routes->get('/units/(:num)', 'Units::show/$1');
$routes->get('/personnel/(:num)', 'Personnel::show/$1');
$routes->get('/equipment/(:num)', 'Equipment::show/$1');
$routes->get('/testgen', 'TestGen');
$routes->get('/planets', 'Planets::index');
$routes->get('/planets/show/(:num)', 'Planets::show/$1');
$routes->get('starsystems', 'StarSystems::index'); 
$routes->get('starsystems/index', 'StarSystems::index');
$routes->get('starsystems/index/(:num)', 'StarSystems::index/$1'); // system_id
$routes->get('starsystems/index/(:num)/(:num)', 'StarSystems::index/$1/$2'); // system_id + planet_id

$routes->get('faction/select', 'Faction::select');
$routes->post('faction/save', 'Faction::save');

$routes->get('equipment/getCrew/(:num)', 'Equipment::getCrew/$1');

// Unit Management Routes
$routes->group('units', ['filter' => 'session'], static function($routes) {
    $routes->get('show/(:num)', 'Units::show/$1');
    $routes->post('assignPersonnel/(:num)', 'Units::assignPersonnel/$1');
    $routes->post('unassignPersonnel/(:num)', 'Units::unassignPersonnel/$1');
    $routes->post('assignEquipment/(:num)', 'Units::assignEquipment/$1');
    $routes->post('unassignEquipment/(:num)', 'Units::unassignEquipment/$1');

    // combined slushbucket management endpoints
    $routes->post('managePersonnel/(:num)', 'Units::managePersonnel/$1');
    $routes->post('manageEquipment/(:num)', 'Units::manageEquipment/$1');

    // commander assignment
    $routes->post('setCommander/(:num)', 'Units::setCommander/$1');
});



service('auth')->routes($routes);

