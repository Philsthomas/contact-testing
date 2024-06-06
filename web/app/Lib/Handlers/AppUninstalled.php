<?php

declare(strict_types=1);

namespace App\Lib\Handlers;

use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Handler;
use App\Models\Session;
use App\Models\Shop;

class AppUninstalled implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {
        Log::debug("App was uninstalled from $shop - removing all sessions");
        Session::where('shop', $shop)->delete();

        $time = time();
        $shopData['shop_token'] = '';
        $shopData['shop_hmac'] = '';
        $shopData['shop_owner_name'] = '';
        $shopData['email'] = '';
        $shopData['uninstall_time'] = $time;
        $shopData['status'] = config('constants.SHOP_STATUS_UNINSTALLED');
        Shop::where('shop_domain', $shop)->update($shopData);
    }
}
