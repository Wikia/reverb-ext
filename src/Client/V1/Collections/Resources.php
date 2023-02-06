<?php

declare(strict_types=1);

namespace Reverb\Client\V1\Collections;

use Tightenco\Collect\Support\Collection;

class Resources extends Collection
{
    /**
     * Meta data associated with the Resources.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Set the meta.
     *
     * @param array $meta
     *
     * @return void
     */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * Get the meta.
     *
     * @return array
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Map items to a callback -- return same instance but with new items.
     *
     * @return \Reverb\Client\V1\Collections\Resources
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);

        $this->replaceAllItems(array_combine($keys, $items));

        return $this;
    }

    /**
     * Replace all items in the Collection.
     *
     * @param array $items
     *
     * @return void
     */
    protected function replaceAllItems(array $items): void
    {
        $this->items = $items;
    }
}
