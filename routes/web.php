<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});


$router->group(['prefix' => 'api'], function () use ($router)
{

   $router->get('/jurnal', 'JurnalController@index');
   $router->post('/jurnal/create', 'JurnalController@store');

   $router->delete('/detail_akun/{id}', 'DetailAkunController@destroy');
   $router->put('/detail_akun/{id}', 'DetailAkunController@update');
   $router->post('/detail_akun', 'DetailAkunController@store');
   $router->get('/detail_akun', 'DetailAkunController@index');






});