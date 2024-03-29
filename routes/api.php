<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', 'Api@register');
Route::post('/login', 'Api@login');
Route::get('/account', 'Api@account');
Route::get('/products', 'Api@products');
Route::get('/posts', 'Api@posts');
Route::get('/add', 'Api@add');
Route::get('/wish', 'Api@wish');
Route::get('/cart', 'Api@cart');
Route::get('/remove', 'Api@remove');
Route::get('/checkout', 'Api@checkout');
Route::match(['get', 'post'],'/payment', 'Api@payment');
Route::match(['get', 'post'],'/pay', 'Api@pay');
Route::any('/paypal', 'Api@paypal');
Route::any('/get_quote', 'Api@get_quote');
Route::post('/review', 'Api@review');
Route::get('/coupon', 'Api@coupon');
Route::get('/subscribe', 'Api@subscribe');
Route::get('/orders', 'Api@orders')->middleware('token');
Route::get('/reviews', 'Api@reviews')->middleware('token');