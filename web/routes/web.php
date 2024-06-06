<?php

use App\Exceptions\ShopifyProductCreatorException;
use Shopify\Utils;
use Shopify\Context;
use App\Models\Session;
use Shopify\Auth\OAuth;
use Shopify\Clients\Rest;
use App\Lib\AuthRedirection;
use App\Lib\EnsureBilling;
use App\Lib\ProductCreator;
use Illuminate\Http\Request;
use Shopify\Webhooks\Topics;
use Shopify\Webhooks\Registry;
use Shopify\Clients\HttpHeaders;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Shopify\Auth\Session as AuthSession;
use Shopify\Exception\InvalidWebhookException;

use App\Models\Shop;

use App\Http\Controllers\FormController;
use App\Http\Controllers\ElementController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
| If you are adding routes outside of the /api path, remember to also add a
| proxy rule for them in web/frontend/vite.config.js
|
*/

Route::fallback(function (Request $request) {
    if (Context::$IS_EMBEDDED_APP &&  $request->query("embedded", false) === "1") {
        if (env('APP_ENV') === 'production') {
            return file_get_contents(public_path('index.html'));
        } else {
            return file_get_contents(base_path('frontend/index.html'));
        }
    } else {
        return redirect(Utils::getEmbeddedAppUrl($request->query("host", null)) . "/" . $request->path());
    }
})->middleware('shopify.installed');

Route::get('/api/auth', function (Request $request) {
    $shop = Utils::sanitizeShopDomain($request->query('shop'));

    // Delete any previously created OAuth sessions that were not completed (don't have an access token)
    Session::where('shop', $shop)->where('access_token', null)->delete();

    return AuthRedirection::redirect($request);
});

Route::get('/api/auth/callback', function (Request $request) {
    $session = OAuth::callback(
        $request->cookie(),
        $request->query(),
        ['App\Lib\CookieHandler', 'saveShopifyCookie'],
    );

    $host = $request->query('host');
    $shop = Utils::sanitizeShopDomain($request->query('shop'));


    ////////////////////// Track shop data ////////////////////////////
    $client = new Rest($session->getShop(), $session->getAccessToken());
    // Get shop details
    $shopResponse = $client->get('/admin/shop.json');
        
    // Check if the request was successful
    if ($shopResponse->getStatusCode() >= 200 && $shopResponse->getStatusCode() < 300) {
        // Parse the response body as JSON
        $shopResponseDecoded = json_decode($shopResponse->getBody(), true);
        $shopDetails = $shopResponseDecoded['shop'];

        try {

            $time = time();
            $query = 'shopFetch';
            $shopExistData = Shop::select('id','shop_token')
            ->where('shop_domain', $shop)
            ->first();

            if($shopExistData){ 

                $query = 'shopUpdate';

                if($shopExistData->shop_token == "") { //previously installed and uninstalled

                    $shopData['shop_token'] = $session->getAccessToken();
                    $shopData['shop_hmac'] = $request->query('hmac');
                    $shopData['shop_owner_name'] = $shopDetails["shop_owner"];
                    $shopData['email'] = $shopDetails["email"];
                    $shopData['install_time'] = $time;
                    $shopData['uninstall_time'] = 0;
                    $shopData['status'] = config('constants.SHOP_STATUS_INSTALLED');
                    Shop::where('shop_domain', $shop)->update($shopData);
                }
                else { //active installation updates hmac from shopify

                    $shopData['shop_hmac'] = $request->query('hmac');
                    Shop::where('shop_domain', $shop)->update($shopData);
                }
            }
            else { //fresh install

                $query = 'shopCreate';
                $shopData['shop_domain'] = $shop;
                $shopData['shop_id'] = $shopDetails["id"];
                $shopData['shop_name'] = $shopDetails["name"];
                $shopData['shop_token'] = $session->getAccessToken();
                $shopData['shop_hmac'] = $request->query('hmac');
                $shopData['shop_owner_name'] = $shopDetails["shop_owner"];
                $shopData['email'] = $shopDetails["email"];
                $shopData['install_time'] = $time;
                $shopData['uninstall_time'] = 0;
                $shopData['status'] = config('constants.SHOP_STATUS_INSTALLED');
                Shop::create($shopData);
            }
        } catch (QueryException $ex) {
                
            switch ($query) {
                case 'shopFetch':
                  $message = trans('commonMessages.shop_fetch_failed');
                  break;
                case 'shopCreate':
                  $message = trans('commonMessages.shop_create_failed');
                  break;
                case 'shopUpdate':
                  $message = trans('commonMessages.shop_updation_failed');
                  break;
              }
              Log::error(
                $message
            );
        } 
    } else {
        // Handle error response
        $error = $shopDetails->json(); // If you want to get the error details
        Log::error(
            'Shop details fetch failed'
        );
    }
   //////////////////////////////////////////////////////////////////////////


    $response = Registry::register('/api/webhooks', Topics::APP_UNINSTALLED, $shop, $session->getAccessToken());
    if ($response->isSuccess()) {
        Log::debug("Registered APP_UNINSTALLED webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register APP_UNINSTALLED webhook for shop $shop with response body: " .
                print_r($response->getBody(), true)
        );
    }

    $redirectUrl = Utils::getEmbeddedAppUrl($host);
    if (Config::get('shopify.billing.required')) {
        list($hasPayment, $confirmationUrl) = EnsureBilling::check($session, Config::get('shopify.billing'));

        if (!$hasPayment) {
            $redirectUrl = $confirmationUrl;
        }
    }

    return redirect($redirectUrl);
});

Route::get('/api/form-view/{formId}',[FormController::class,'view'])->middleware('shopify.auth');
Route::delete('/api/form-delete/{formId}', [FormController::class,'delete'])->middleware('shopify.auth');
Route::get('/api/form-list', [FormController::class, 'list'])->middleware('shopify.auth');
Route::get('/api/form-template-list', [FormController::class, 'templateList'])->middleware('shopify.auth');
Route::get('/api/element-view/{formId}/{elementName}', [ElementController::class, 'view'])->middleware('shopify.auth');
Route::post('/api/element-delete', [ElementController::class, 'delete'])->middleware('shopify.auth');

Route::post('/api/element-sort', [ElementController::class,'sort'])->middleware('shopify.auth');
Route::post('/api/element-update', [ElementController::class,'update'])->middleware('shopify.auth');
Route::post('/api/element-add', [ElementController::class,'add'])->middleware('shopify.auth');
Route::post('/api/form-create', [FormController::class,'create'])->middleware('shopify.auth');
Route::put('/api/form-update/{formId}',[FormController::class,'update'])->middleware('shopify.auth');



Route::post('/api/webhooks', function (Request $request) {
    try {
        $topic = $request->header(HttpHeaders::X_SHOPIFY_TOPIC, '');

        $response = Registry::process($request->header(), $request->getContent());
        if (!$response->isSuccess()) {
            Log::error("Failed to process '$topic' webhook: {$response->getErrorMessage()}");
            return response()->json(['message' => "Failed to process '$topic' webhook"], 500);
        }
    } catch (InvalidWebhookException $e) {
        Log::error("Got invalid webhook request for topic '$topic': {$e->getMessage()}");
        return response()->json(['message' => "Got invalid webhook request for topic '$topic'"], 401);
    } catch (\Exception $e) {
        Log::error("Got an exception when handling '$topic' webhook: {$e->getMessage()}");
        return response()->json(['message' => "Got an exception when handling '$topic' webhook"], 500);
    }
});
 Route::get('/api/csrf-token', fn () => ['csrf_token' => csrf_token()]);
