<?php

namespace Tests\Feature;

use App\Models\Concerns\BelongsToCustomer;
use App\Models\User;
use App\Policies\OwnedByCustomerPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IsolationProbe extends Model
{
    use BelongsToCustomer;

    protected $table = 'isolation_probes';

    protected $guarded = [];

    public $timestamps = false;
}

class IsolationProbePolicy extends OwnedByCustomerPolicy {}

/**
 * Proves the ownership foundation with a throw-away TEMPORARY table (no schema change, no implicit commit),
 * so every later customer-owned model can rely on the same behaviour.
 */
class CustomerIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('isolation_probes', function (Blueprint $table) {
            $table->temporary();
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('label');
        });
        Gate::policy(IsolationProbe::class, IsolationProbePolicy::class);
    }

    public function test_owned_by_returns_only_the_customers_own_records(): void
    {
        $ada = User::factory()->create();
        $bola = User::factory()->create();
        IsolationProbe::create(['customer_id' => $ada->id, 'label' => 'ada-1']);
        IsolationProbe::create(['customer_id' => $ada->id, 'label' => 'ada-2']);
        IsolationProbe::create(['customer_id' => $bola->id, 'label' => 'bola-1']);

        $this->assertEqualsCanonicalizing(['ada-1', 'ada-2'], IsolationProbe::ownedBy($ada)->pluck('label')->all());
        $this->assertSame(['bola-1'], IsolationProbe::ownedBy($bola)->pluck('label')->all());
        $this->assertNull(IsolationProbe::ownedBy($ada)->where('label', 'bola-1')->first());
    }

    public function test_the_owner_may_view_update_and_delete(): void
    {
        $ada = User::factory()->create();
        $record = IsolationProbe::create(['customer_id' => $ada->id, 'label' => 'mine']);

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue(Gate::forUser($ada)->allows($ability, $record), $ability);
        }
        $this->assertTrue($record->isOwnedBy($ada));
    }

    public function test_another_customer_is_told_the_record_does_not_exist(): void
    {
        $ada = User::factory()->create();
        $bola = User::factory()->create();
        $record = IsolationProbe::create(['customer_id' => $ada->id, 'label' => 'private']);

        foreach (['view', 'update', 'delete'] as $ability) {
            $response = Gate::forUser($bola)->inspect($ability, $record);
            $this->assertTrue($response->denied(), $ability);
            $this->assertSame(404, $response->status(), "{$ability} must look like 'not found', not 'forbidden'");
        }
        $this->assertFalse($record->isOwnedBy($bola));
    }

    public function test_staff_get_no_implicit_access_to_customer_records(): void
    {
        $ada = User::factory()->create();
        $record = IsolationProbe::create(['customer_id' => $ada->id, 'label' => 'private']);

        foreach ([$this->superAdmin(), User::factory()->staff()->create()] as $staff) {
            foreach (['view', 'update', 'delete'] as $ability) {
                $this->assertSame(404, Gate::forUser($staff)->inspect($ability, $record)->status(), $ability);
            }
        }
    }

    public function test_a_model_without_the_ownership_trait_is_never_allowed(): void
    {
        $ada = User::factory()->create();
        $notOwnable = new class extends Model
        {
            protected $guarded = [];
        };
        $notOwnable->customer_id = $ada->id;
        Gate::policy($notOwnable::class, IsolationProbePolicy::class);

        $this->assertSame(404, Gate::forUser($ada)->inspect('view', $notOwnable)->status());
    }
}
