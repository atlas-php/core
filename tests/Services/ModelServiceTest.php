<?php

declare(strict_types=1);

namespace Atlas\Core\Tests\Services;

use Atlas\Core\Services\ModelService;
use Atlas\Core\Tests\Fixtures\TestAtlasModel;
use Atlas\Core\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Mockery;
use stdClass;

/**
 * Class ModelServiceTest
 *
 * Validates the reusable CRUD helpers exposed by the ModelService abstraction.
 * PRD Reference: Atlas Core Extraction Plan — Shared data service helpers.
 */
class ModelServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('atlas-testing.tables.widgets', 'atlas_widgets');
        config()->set('atlas-testing.database.connection', 'atlas_core_testbench');

        Schema::create('atlas_widgets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('atlas_widgets');

        Mockery::close();
        parent::tearDown();
    }

    public function test_list_and_paginate_records(): void
    {
        $service = new StubWidgetService;
        TestAtlasModel::query()->create(['name' => 'alpha']);
        TestAtlasModel::query()->create(['name' => 'bravo']);

        $records = $service->list(options: [
            'query' => static fn (Builder $builder): Builder => $builder->where('name', 'alpha'),
        ]);
        $this->assertCount(1, $records);
        $this->assertSame('alpha', $records->first()->name);

        $paginated = $service->listPaginated(1, ['sortField' => 'name', 'sortOrder' => 0]);
        $this->assertSame(2, $paginated->total());
        $this->assertSame('bravo', $paginated->items()[0]->name);
    }

    public function test_create_update_and_delete_flow(): void
    {
        $service = new StubWidgetService;

        $model = $service->create(['name' => 'alpha']);
        $this->assertSame('alpha', $model->name);

        $updated = $service->updateByKey($model->getKey(), ['name' => 'gamma']);
        $this->assertSame('gamma', $updated->name);

        $this->assertTrue($service->delete($updated));
        $this->assertDatabaseMissing($updated->getTable(), ['id' => $updated->getKey()]);
    }

    public function test_update_by_key_throws_when_missing(): void
    {
        $service = new StubWidgetService;

        $this->expectException(ModelNotFoundException::class);
        $service->updateByKey(999, ['name' => 'ghost']);
    }

    public function test_service_requires_model_definition(): void
    {
        $service = new class extends ModelService {};

        $this->expectException(LogicException::class);
        $service->query();
    }

    public function test_find_or_fail_returns_model(): void
    {
        $service = new StubWidgetService;
        $created = TestAtlasModel::query()->create(['name' => 'delta']);

        $found = $service->findOrFail($created->getKey());

        $this->assertSame($created->getKey(), $found->getKey());
    }

    public function test_find_or_fail_throws_when_model_missing(): void
    {
        $service = new StubWidgetService;

        $this->expectException(ModelNotFoundException::class);
        $service->findOrFail(12345);
    }

    public function test_resolve_model_class_rejects_non_model_class_strings(): void
    {
        $service = new InvalidModelServiceStub;

        $this->expectException(LogicException::class);
        $service->exposeResolveModelClass();
    }

    public function test_apply_query_options_invokes_callback_and_eager_loaders(): void
    {
        $service = new StubWidgetService;
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('with')->once()->with(['relation'])->andReturnSelf();
        $builder->shouldReceive('withCount')->once()->with('counts')->andReturnSelf();

        $callbackInvoked = false;

        $service->applyOptionsForTest($builder, [
            'query' => function (Builder $passed) use ($builder, &$callbackInvoked): void {
                $this->assertSame($builder, $passed);
                $callbackInvoked = true;
            },
            'with' => ['relation'],
            'withCount' => 'counts',
        ]);

        $this->assertTrue($callbackInvoked);
    }
}

/**
 * Class StubWidgetService
 *
 * Minimal ModelService implementation for exercising shared CRUD flows.
 * PRD Reference: Atlas Core Extraction Plan — Shared data service helpers.
 *
 * @extends ModelService<TestAtlasModel>
 */
class StubWidgetService extends ModelService
{
    protected string $model = TestAtlasModel::class;

    public function buildQuery(array $options = []): Builder
    {
        $query = parent::buildQuery($options);

        if (isset($options['name'])) {
            $query->where('name', $options['name']);
        }

        return $query;
    }

    /**
     * @param  Builder<\Atlas\Core\Tests\Fixtures\TestAtlasModel>  $builder
     * @param  array<string, mixed>  $options
     * @return Builder<\Atlas\Core\Tests\Fixtures\TestAtlasModel>
     */
    public function applyOptionsForTest(Builder $builder, array $options): Builder
    {
        return $this->applyQueryOptions($builder, $options);
    }
}

/**
 * Class InvalidModelServiceStub
 *
 * Provides a misconfigured service for testing resolveModelClass guards.
 * PRD Reference: Atlas Core Extraction Plan — Shared data service helpers.
 *
 * @extends ModelService<stdClass>
 */
class InvalidModelServiceStub extends ModelService
{
    protected string $model = stdClass::class;

    public function exposeResolveModelClass(): string
    {
        return $this->resolveModelClass();
    }
}
