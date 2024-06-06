<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;
    protected $fillable = [
        'shop_id',
        'shop_domain',
        'shop_name',
        'shop_token',
        'shop_hmac',
        'shop_owner_name',
        'email',
        'install_time',
        'uninstall_time',
        'status'
    ];

}
