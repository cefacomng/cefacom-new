<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_entry_records_who_what_target_and_before_after_values(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $entry = app(AuditLogger::class)->record($actor, 'example.changed', $target, ['plan' => 'a'], ['plan' => 'b']);

        $entry->refresh();
        $this->assertSame($actor->id, $entry->actor_id);
        $this->assertSame('example.changed', $entry->action);
        $this->assertSame($target->id, $entry->target_id);
        $this->assertSame(['plan' => 'a'], $entry->before);
        $this->assertSame(['plan' => 'b'], $entry->after);
        $this->assertNotNull($entry->created_at);
    }

    public function test_secret_looking_values_are_redacted_at_any_depth(): void
    {
        $entry = app(AuditLogger::class)->record(null, 'provider.changed', null, [], [
            'name' => 'Termii', 'api_key' => 'sk_live_123', 'Password' => 'hunter2',
            'settings' => ['webhook_secret' => 'whsec_1', 'region' => 'ng', 'nested' => ['access_token' => 'tok']],
            'Authorization' => 'Bearer abc',
        ])->refresh();

        $this->assertSame('Termii', $entry->after['name']);
        $this->assertSame('[redacted]', $entry->after['api_key']);
        $this->assertSame('[redacted]', $entry->after['Password']);
        $this->assertSame('[redacted]', $entry->after['Authorization']);
        $this->assertSame('[redacted]', $entry->after['settings']['webhook_secret']);
        $this->assertSame('[redacted]', $entry->after['settings']['nested']['access_token']);
        $this->assertSame('ng', $entry->after['settings']['region']);
        $this->assertStringNotContainsString('sk_live_123', json_encode(AuditLog::all()));
    }

    public function test_entries_cannot_be_changed_or_deleted(): void
    {
        $entry = app(AuditLogger::class)->record(null, 'example.happened');

        try {
            $entry->update(['action' => 'tampered']);
            $this->fail('Update should be refused');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }
        try {
            $entry->delete();
            $this->fail('Delete should be refused');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame('example.happened', $entry->fresh()->action);
    }

    public function test_the_actor_id_survives_when_the_account_is_removed(): void
    {
        $actor = User::factory()->create();
        $entry = app(AuditLogger::class)->record($actor, 'example.happened');

        $actorId = $actor->id;
        $actor->delete();

        $this->assertSame($actorId, $entry->fresh()->actor_id);
    }
}
