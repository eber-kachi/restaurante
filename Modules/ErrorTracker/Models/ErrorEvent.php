<?php

namespace Modules\ErrorTracker\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorEvent extends Model
{
    protected $table = 'error_tracker_events';

    protected $guarded = ['id'];

    protected $casts = [
        'context' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_notified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function getShortClassAttribute(): string
    {
        return $this->exception_class ? class_basename($this->exception_class) : strtoupper($this->level);
    }
}
