<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Merekam aktivitas baru ke tabel activity_logs.
     */
    public function log(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $properties = null,
        ?User $user = null
    ): ActivityLog {
        $actor = $user ?? auth()->user();

        return ActivityLog::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name ?? 'Sistem / Guest',
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
