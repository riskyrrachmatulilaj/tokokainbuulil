<?php

namespace App\Models\Concerns;

use App\Models\DataChangeRequest;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasDataChangeRequests
{
    public function changeRequests(): MorphMany
    {
        return $this->morphMany(DataChangeRequest::class, 'requestable');
    }

    public function pendingChangeRequest(): MorphOne
    {
        return $this->morphOne(DataChangeRequest::class, 'requestable')
            ->where('status', DataChangeRequest::STATUS_PENDING);
    }

    public function hasPendingChangeRequest(): bool
    {
        return $this->changeRequests()
            ->where('status', DataChangeRequest::STATUS_PENDING)
            ->exists();
    }
}
