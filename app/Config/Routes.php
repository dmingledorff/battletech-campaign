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

service('auth')->routes($routes);

