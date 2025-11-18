<?php

declare(strict_types=1);

namespace Atlas\Core\Models;

use Atlas\Core\Config\TableResolver;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Class AtlasModel
 *
 * Base Eloquent model that centralizes Atlas config-driven table naming and connections.
 * PRD Reference: Atlas Core Extraction Plan — Shared data abstractions.
 */
abstract class AtlasModel extends Model
{
    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    protected string $configPrefix = 'atlas';

    protected string $tableKey = '';

    private ?TableResolver $tableResolver = null;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setTable($this->resolveConfiguredTableName());

        $connection = $this->resolveConfiguredConnection();

        if ($connection !== null) {
            $this->setConnection($connection);
        }

        parent::__construct($attributes);
    }

    abstract protected function defaultTableName(): string;

    protected function resolveConfiguredTableName(): string
    {
        return $this->tableResolver()->resolve($this->tableKey(), $this->defaultTableName());
    }

    protected function resolveConfiguredConnection(): ?string
    {
        return $this->tableResolver()->resolveConnection();
    }

    protected function tableKey(): string
    {
        $key = trim($this->tableKey);

        if ($key === '') {
            throw new LogicException(sprintf('A %s implementation must define a table key.', static::class));
        }

        return $key;
    }

    protected function configPrefix(): string
    {
        $prefix = trim($this->configPrefix);

        if ($prefix === '') {
            throw new LogicException(sprintf('A %s implementation must define a configuration prefix.', static::class));
        }

        return $prefix;
    }

    protected function tableResolver(): TableResolver
    {
        if ($this->tableResolver === null) {
            $this->tableResolver = new TableResolver($this->configPrefix());
        }

        return $this->tableResolver;
    }
}
