<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpinHistory extends Model
{
    protected $fillable = ['user_id', 'prize_id', 'is_claimed', 'claimed_at'];

    protected function casts(): array
    {
        return ['is_claimed' => 'boolean', 'claimed_at' => 'datetime'];
    }

    public function prize()
    {
        return $this->belongsTo(Prize::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}