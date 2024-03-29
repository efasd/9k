<?php
/**
 * File name: api.php
 * Last modified: 2020.04.30 at 08:21:08
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

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

Route::prefix('driver')->group(function () {
    Route::post('login', 'API\Driver\UserAPIController@login');
    Route::post('register', 'API\Driver\UserAPIController@register');
    Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
    Route::get('user', 'API\Driver\UserAPIController@user');
    Route::get('logout', 'API\Driver\UserAPIController@logout');
    Route::get('settings', 'API\Driver\UserAPIController@settings');
});

Route::prefix('manager')->group(function () {
    Route::post('login', 'API\Manager\UserAPIController@login');
    Route::post('register', 'API\Manager\UserAPIController@register');
    Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
    Route::get('user', 'API\Manager\UserAPIController@user');
    Route::get('logout', 'API\Manager\UserAPIController@logout');
    Route::get('settings', 'API\Manager\UserAPIController@settings');

    Route::get('employee/{marketId}', 'API\UserAPIController@getEmployee');
    Route::post('employee/appointment', 'API\UserAPIController@setEmployee');
});


Route::post('login', 'API\UserAPIController@login');
Route::post('register', 'API\UserAPIController@register');
Route::post('send_reset_link_email', 'API\UserAPIController@sendResetLinkEmail');
Route::get('user', 'API\UserAPIController@user');
Route::get('logout', 'API\UserAPIController@logout');
Route::get('settings', 'API\UserAPIController@settings');

Route::resource('fields', 'API\FieldAPIController');
Route::resource('categories', 'API\CategoryAPIController');
Route::resource('markets', 'API\MarketAPIController');

Route::resource('faq_categories', 'API\FaqCategoryAPIController');
Route::get('products/categories', 'API\ProductAPIController@categories');
Route::resource('products', 'API\ProductAPIController');
Route::resource('galleries', 'API\GalleryAPIController');
Route::resource('product_reviews', 'API\ProductReviewAPIController');   

Route::prefix('product')->group(function () {
    Route::get('employee/{id}', 'API\ProductAPIController@getEmployees');
});

Route::prefix('employee')->group(function () {
    Route::get('appointment/{userId}', 'API\UserAPIController@getEmployees');
    Route::post('appointment/getAppointment', 'API\UserAPIController@getEmployeeAppointment');
    Route::post('appointment/setAppointment', 'API\UserAPIController@setEmployeeAppointment');
});

Route::prefix('payment')->group(function () {
   Route::prefix('auth')->group(function() {
       Route::post('token', 'API\payment\auth\PaymentAuthAPIController@token');
       Route::post('refresh', 'API\payment\auth\PaymentAuthAPIController@refresh');
   });
   Route::prefix('invoice')->group(function() {
       Route::post('create', 'API\payment\invoice\PaymentAPIInvoiceController@create');
       Route::post('create-simple', 'API\payment\invoice\PaymentAPIInvoiceController@createSimple');
       Route::get('get/{invoiceId}', 'API\payment\invoice\PaymentAPIInvoiceController@get');
       Route::delete('cancel/{invoiceId}', 'API\payment\invoice\PaymentAPIInvoiceController@cancel');
       Route::get('qpay-check/{orderId}', 'API\payment\invoice\PaymentAPIInvoiceController@qPayChecker');
   });
   Route::prefix('payment')->group(function() {
       Route::get('get/{invoiceId}', 'API\payment\payment\PaymentAPIController@get');
       Route::get('check/{invoiceId}', 'API\payment\payment\PaymentAPIController@check');
       Route::delete('cancel', 'API\payment\payment\PaymentAPIController@cancel');
       Route::delete('refund', 'API\payment\payment\PaymentAPIController@refund');
       Route::post('list', 'API\payment\payment\PaymentAPIController@list');
   });
});

Route::resource('faqs', 'API\FaqAPIController');
Route::resource('market_reviews', 'API\MarketReviewAPIController');
Route::resource('currencies', 'API\CurrencyAPIController');
Route::resource('slides', 'API\SlideAPIController')->except([
    'show'
]);

Route::resource('option_groups', 'API\OptionGroupAPIController');

Route::resource('options', 'API\OptionAPIController');

Route::post('category/getAppointment', 'OrderCalendarController@getAppointmentMonth');

Route::middleware('auth:api')->group(function () {
    Route::group(['middleware' => ['role:driver']], function () {
        Route::prefix('driver')->group(function () {
            Route::resource('orders', 'API\OrderAPIController');
            Route::resource('notifications', 'API\NotificationAPIController');
            Route::post('users/{id}', 'API\UserAPIController@update');
            Route::resource('faq_categories', 'API\FaqCategoryAPIController');
            Route::resource('faqs', 'API\FaqAPIController');
        });
    });
    Route::group(['middleware' => ['role:manager']], function () {
        Route::prefix('manager')->group(function () {
            Route::post('users/{id}', 'API\UserAPIController@update');
            Route::get('users/drivers_of_market/{id}', 'API\Manager\UserAPIController@driversOfMarket');
            Route::get('dashboard/{id}', 'API\DashboardAPIConstroller@manager');
            Route::resource('markets', 'API\Manager\MarketAPIController');

            Route::post('employee/{marketId}/{$userId}', 'API\UserAPIController@setEmployee');
        });
    });
    Route::post('users/{id}', 'API\UserAPIController@update');

    Route::resource('order_statuses', 'API\OrderStatusAPIController');

    Route::get('payments/byMonth', 'API\PaymentAPIController@byMonth')->name('payments.byMonth');
    Route::resource('payments', 'API\PaymentAPIController');

    Route::get('favorites/exist', 'API\FavoriteAPIController@exist');
    Route::resource('favorites', 'API\FavoriteAPIController');
    Route::resource('orders', 'API\OrderAPIController');

    Route::resource('product_orders', 'API\ProductOrderAPIController');

    Route::resource('notifications', 'API\NotificationAPIController');

    Route::get('carts/count', 'API\CartAPIController@count')->name('carts.count');
    Route::resource('carts', 'API\CartAPIController');

    Route::resource('delivery_addresses', 'API\DeliveryAddressAPIController');

    Route::resource('drivers', 'API\DriverAPIController');

    Route::resource('earnings', 'API\EarningAPIController');

    Route::resource('driversPayouts', 'API\DriversPayoutAPIController');

    Route::resource('marketsPayouts', 'API\MarketsPayoutAPIController');

    Route::resource('coupons', 'API\CouponAPIController')->except([
        'show'
    ]);

    Route::prefix('employees')->group(function () {
        Route::get('market/{marketId}', 'API\EmployeeController@index');
        Route::get('market/{marketId}/{employeeId}', 'API\EmployeeController@findOne');
        Route::put('market/{marketId}/{employeeId}', 'API\EmployeeController@update');
        Route::post('market/{marketId}', 'API\EmployeeController@create');
        Route::delete('market/{marketId}/{employeeId}', 'API\EmployeeController@destroy');
    });
});