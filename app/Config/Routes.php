<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Dashboard::index');
$routes->get('/units/(:num)', 'Units::show/$1');
$routes->get('/personnel/(:num)', 'Personnel::show/$1');
$routes->get('/equipment/(:num)', 'Equipment::show/$1');
$routes->get('/testgen', 'TestGen');
