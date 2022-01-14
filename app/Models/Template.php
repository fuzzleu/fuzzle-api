<?php

namespace App\Models;

class Template extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'canvas_width',
        'canvas_height',
        'data',
        'public'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'name' => 'string',
        'canvas_width' => 'integer',
        'canvas_height' => 'integer',
        'thumbnail' => 'string',
        'data' => 'string',
        'public' => 'bool'
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
