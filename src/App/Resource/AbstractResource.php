<?php

namespace App\Resource;

abstract class AbstractResource
{
    /** @var array|object */
    protected array|object $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    /**
     * Transform the item to array format
     *
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * Transform a collection of items
     * 
     * @param object[] $items
     * @return array<int, array<string, mixed>>
     */
    public static function collection(array $items): array
    {
        return array_map(fn($item) => (new static($item))->toArray(), $items);
    }

    
} 