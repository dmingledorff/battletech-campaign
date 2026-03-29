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
$routes->get('dashboard/unitChildren/(:num)', 'Dashboard::unitChildren/$1');

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

$routes->get('units',                        'Units::index');
$routes->post('units/store',                 'Units::store');
$routes->post('units/(:num)/updateName',     'Units::updateName/$1');
$routes->post('units/(:num)/deactivate',     'Units::deactivate/$1');

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
$routes->get('missions/getUnitRoster/(:num)', 'Missions::getUnitRoster/$1');
$routes->get('missions/getUnitsForMap/(:num)', 'Missions::getUnitsForMap/$1');

$routes->get('toe', 'ToeBuilder::index');
$routes->get('toe/create', 'ToeBuilder::create');
$routes->post('toe/store', 'ToeBuilder::store');
$routes->get('toe/(:num)', 'ToeBuilder::show/$1');
$routes->get('toe/(:num)/edit', 'ToeBuilder::edit/$1');
$routes->post('toe/(:num)/update', 'ToeBuilder::update/$1');
$routes->post('toe/(:num)/delete', 'ToeBuilder::delete/$1');

// ================================
// TOE
// ================================
// Slot management (AJAX)
$routes->post('toe/(:num)/slots/add', 'ToeBuilder::addSlot/$1');
$routes->post('toe/slots/(:num)/delete', 'ToeBuilder::deleteSlot/$1');
$routes->post('toe/slots/(:num)/crew/add', 'ToeBuilder::addCrew/$1');
$routes->post('toe/crews/(:num)/delete', 'ToeBuilder::deleteCrew/$1');

// Subunit management (AJAX)
$routes->post('toe/(:num)/subunits/add', 'ToeBuilder::addSubunit/$1');
$routes->post('toe/subunits/(:num)/delete', 'ToeBuilder::deleteSubunit/$1');

// ================================
// Event log
// ================================
$routes->get('eventlog',      'EventLog::index');
$routes->get('eventlog/(:num)', 'EventLog::index/$1');

// ================================
// Admin panel
// ================================
$routes->get('admin',                    'Admin::index');
$routes->post('admin/generateUnit',      'Admin::generateUnit');
$routes->post('admin/setDate',           'Admin::setDate');
$routes->post('admin/moveUnit',          'Admin::moveUnit');
$routes->post('admin/sendLog',           'Admin::sendLog');
$routes->post('admin/tick',              'Admin::tick');

// ================================
// Auth (CodeIgniter Shield)
// ================================
service('auth')->routes($routes);