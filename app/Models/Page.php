<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        "title",
        "slug",
        "content",
        "excerpt",
        "status",
    ];
    
    protected $casts = [
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];
}
