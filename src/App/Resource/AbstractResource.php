<?php

namespace App\Resource;

abstract class AbstractResource
{
    /** @var object DTO */
    protected object $item;

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
     * @param array $items
     * @return array
     */
    public static function collection(array $items): array
    {
        return array_map(function ($item) {
            return (new static($item))->toArray();
        }, $items);
    }
    
} 