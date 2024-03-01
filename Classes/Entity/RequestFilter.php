<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Entity;

/**
 * Class RequestFilter
 */
class RequestFilter
{
    protected string $processedName = '';
    protected ?array $values = null;
    protected ?array $schema = null;
    protected bool $multiple = false;

    /**
     * RequestFilter constructor.
     */
    public function __construct(protected string $name, protected ?string $label = null, protected mixed $default = null, protected string|int|bool|array|null $value = null, bool $multiple = false)
    {
        $this->processedName = $multiple ? str_replace('[]', '', $name) : $name;
        $this->multiple = $multiple;
        $this->values = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getValues(): ?array
    {
        return $this->values;
    }

    public function setValues(?array $values): void
    {
        $this->values = $values;
    }

    public function addValue(string $key, string $value): void
    {
        $this->values[$key] = $value;
    }

    public function getSchema(): ?array
    {
        return $this->schema;
    }

    public function setSchema(?array $schema): void
    {
        $this->schema = $schema;
    }

    public function getValue(): string|int|bool|array|null
    {
        return !is_null($this->value) ? $this->value : $this->default;
    }

    public function setValue(string|int|bool|array|null $value): void
    {
        $this->value = $value;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return mixed|null
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * @param mixed|null $default
     */
    public function setDefault(mixed $default): void
    {
        $this->default = $default;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): void
    {
        $this->multiple = $multiple;
    }

    public function getProcessedName(): string
    {
        return $this->processedName;
    }

    public function setProcessedName(string $processedName): void
    {
        $this->processedName = $processedName;
    }
}
