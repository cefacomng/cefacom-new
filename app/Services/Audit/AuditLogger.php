<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Records sensitive actions: who, what, on which target, when, and before/after values.
 * Secret-looking values are redacted so credentials never reach the audit trail.
 */
class AuditLogger
{
    private const SECRET_FRAGMENTS = ['password', 'secret', 'token', 'api_key', 'apikey', 'credential', 'authorization', 'private_key'];

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function record(?User $actor, string $action, ?Model $target = null, array $before = [], array $after = []): AuditLog
    {
        return AuditLog::create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'target_type' => $target ? $target->getMorphClass() : null,
            'target_id' => $target?->getKey(),
            'before' => $before === [] ? null : $this->redact($before),
            'after' => $after === [] ? null : $this->redact($after),
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        $clean = [];
        foreach ($values as $key => $value) {
            if (is_string($key) && $this->looksSecret($key)) {
                $clean[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $clean[$key] = $this->redact($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private function looksSecret(string $key): bool
    {
        $key = strtolower($key);
        foreach (self::SECRET_FRAGMENTS as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
