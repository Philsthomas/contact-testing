<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormInstallation extends Model
{
    use HasFactory;
    protected $fillable = [
        'form_id',
        'page_type',
        'page_id',
        'theme_id',
       
    ];
}
