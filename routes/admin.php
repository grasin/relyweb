<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Admin routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "Admin" middleware group. Enjoy building your Admin!
|
*/

Route::match(['get', 'post'],'/login', 'Admin@login');
Route::get('/', 'Admin@index')->middleware('Login');
Route::get('/logout', 'Admin@logout')->middleware('Login');
Route::get('/map', 'Admin@map')->middleware('Login');
Route::match(['get', 'post'],'/products/{action?}/{id?}', 'Admin@products')->middleware('Login');
Route::match(['get', 'post'],'/categories/{action?}/{id?}', 'Admin@categories')->middleware('Login');
Route::match(['get', 'post'],'/pages/{action?}/{id?}', 'Admin@pages')->middleware('Login');
Route::match(['get', 'post'],'/blog/{action?}/{id?}', 'Admin@blog')->middleware('Login');
Route::get('/customers/{action?}/{id?}', 'Admin@customers')->middleware('Login');
Route::match(['get', 'post'],'/coupons/{action?}/{id?}', 'Admin@coupons')->middleware('Login');
Route::match(['get', 'post'],'/shipping/{action?}/{id?}', 'Admin@shipping')->middleware('Login');
Route::match(['get', 'post'],'/reviews/{action?}/{id?}', 'Admin@reviews')->middleware('Login');
Route::match(['get', 'post'],'/orders/{action?}/{id?}', 'Admin@orders')->middleware('Login');
Route::match(['get', 'post'],'/rfqs/{action?}/{id?}', 'Admin@rfqs')->middleware('Login');
Route::get('/statistics/{term?}', 'Admin@statistics')->middleware('Login');
Route::match(['get', 'post'],'/tracking/{action?}/{id?}', 'Admin@tracking')->middleware('Login');
Route::match(['get', 'post'],'/newsletter', 'Admin@newsletter')->middleware('Login');
Route::get('/referrers', 'Admin@referrers')->middleware('Login');
Route::get('/os', 'Admin@os')->middleware('Login');
Route::get('/browsers', 'Admin@browsers')->middleware('Login');
Route::match(['get', 'post'],'/payment/{action?}/{id?}', 'Admin@payment')->middleware('Login');
Route::match(['get', 'post'],'/currency/{action?}/{id?}', 'Admin@currency')->middleware('Login');
Route::match(['get', 'post'],'/settings', 'Admin@settings')->middleware('Login');
Route::match(['get', 'post'],'/theme', 'Admin@theme')->middleware('Login');
Route::match(['get', 'post'],'/languages/{action?}/{id?}', 'Admin@languages')->middleware('Login');
Route::get('/tokens/{action?}/{id?}', 'Admin@tokens')->middleware('Login');
Route::get('/export/{action?}', 'Admin@export')->middleware('Login');
Route::match(['get', 'post'],'/slider/{action?}/{id?}', 'Admin@slider')->middleware('Login');
Route::match(['get', 'post'],'/editor/{file?}', 'Admin@editor')->middleware('Login');
Route::match(['get', 'post'],'/templates/{action?}/{id?}', 'Admin@templates')->middleware('Login');
Route::match(['get', 'post'],'/builder/{action?}/{id?}', 'Admin@builder')->middleware('Login');
Route::match(['get', 'post'],'/menu/{action?}/{id?}', 'Admin@menu')->middleware('Login');
Route::match(['get', 'post'],'/bottom/{action?}/{id?}', 'Admin@bottom')->middleware('Login');
Route::match(['get', 'post'],'/fields/{action?}/{id?}', 'Admin@fields')->middleware('Login');
Route::match(['get', 'post'],'/support/{action?}/{id?}', 'Admin@support')->middleware('Login');
Route::match(['get', 'post'],'/administrators/{action?}/{id?}', 'Admin@administrators')->middleware('Login');
Route::match(['get', 'post'],'/profile', 'Admin@profile')->middleware('Login');
