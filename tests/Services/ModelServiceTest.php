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
}

/**
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
}
