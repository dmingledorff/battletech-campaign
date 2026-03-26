<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ================================
// General
// ================================
$routes->get('/', 'Home::index');
$routes->get('/dashboard', 'Dashboard::index');

// ================================
// Faction
// ================================
$routes->get('faction/select', 'Faction::select');
$routes->post('faction/save', 'Faction::save');

// ================================
// Star Systems & Planets
// ================================
$routes->get('starsystems', 'StarSystems::index');
$routes->get('starsystems/(:num)', 'StarSystems::index/$1');
$routes->get('starsystems/(:num)/(:num)', 'StarSystems::index/$1/$2');

$routes->get('planets', 'Planets::index');
$routes->get('planets/(:num)', 'Planets::show/$1');

// ================================
// Locations
// ================================
$routes->get('location/(:num)', 'Locations::show/$1');

// ================================
// Units
// ================================
$routes->get('units/(:num)', 'Units::show/$1');
$routes->get('units/byParent/(:num)', 'Units::byParent/$1');

$routes->post('units/managePersonnel/(:num)', 'Units::managePersonnel/$1');
$routes->post('units/manageEquipment/(:num)', 'Units::manageEquipment/$1');
$routes->post('units/assignCommander/(:num)', 'Units::assignCommander/$1');
$routes->post('units/dismissCommander/(:num)', 'Units::dismissCommander/$1');
$routes->post('units/setCommander/(:num)', 'Units::setCommander/$1');

// ================================
// Personnel
// ================================
$routes->get('personnel/roster', 'Personnel::roster');
$routes->get('personnel/(:num)', 'Personnel::show/$1');

// ================================
// Equipment
// ================================
$routes->get('equipment/(:num)', 'Equipment::show/$1');
$routes->get('equipment/getCrew/(:num)', 'Equipment::getCrew/$1');
$routes->get('equipment/getAvailableCrew/(:num)/(:num)', 'Equipment::getAvailableCrew/$1/$2');
$routes->post('equipment/assignCrew/(:num)', 'Equipment::assignCrew/$1');
$routes->post('equipment/removeCrew/(:num)', 'Equipment::removeCrew/$1');

// ================================
// Missions
// ================================
$routes->get('missions', 'Missions::index');
$routes->get('missions/create', 'Missions::create');
$routes->get('missions/(:num)', 'Missions::show/$1');
$routes->get('missions/getUnitsAtLocation/(:num)', 'Missions::getUnitsAtLocation/$1');
$routes->get('missions/getLocations', 'Missions::getLocations');
$routes->post('missions/store', 'Missions::store');
$routes->post('missions/update/(:num)', 'Missions::update/$1');
$routes->post('missions/launch/(:num)', 'Missions::launch/$1');
$routes->post('missions/abort/(:num)', 'Missions::abort/$1');

// ================================
// Dev / Testing
// ================================
$routes->get('/testgen', 'TestGen');

// ================================
// Auth (CodeIgniter Shield)
// ================================
service('auth')->routes($routes);