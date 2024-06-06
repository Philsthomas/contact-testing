<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormTemplate extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'img',
        'img_url',
        'layout',
        'form_body_html',
        'form_element_html',
        'form_submit_html'
    ];
}
