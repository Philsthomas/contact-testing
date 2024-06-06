<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'template_id',
        'shop_id',
        'fields',
        'code',
        'status',
        'after_submission',
        'redirect_url',
        'thanks_message',
        'submit_button_text',
        'submit_button_class'
    ];

}
