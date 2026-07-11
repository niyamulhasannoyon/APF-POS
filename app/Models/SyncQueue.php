<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['record_type', 'record_uuid', 'payload', 'retry_count', 'error_message', 'processed_at'])]
class SyncQueue extends Model
{
    protected $table = 'sync_queue';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
