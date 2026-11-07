<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = ['provider', 'status', 'trigger', 'fetched', 'created', 'updated', 'skipped', 'missing', 'message', 'details', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['details' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }
}
