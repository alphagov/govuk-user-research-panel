<?php namespace squiz\surveys\entities;

class GenericCollection implements \IteratorAggregate, \Countable
{
    /**
     * Items
     * @var array
     */
    protected $items = array();
    /**
     * Method to implement IteratorAggregate interface
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Method to implement Countable interface
     * @return int|void
     */
    public function count()
    {
        return count($this->items);
    }

    public function addItem($item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    public function isEmpty()
    {
        return empty($this->items);
    }

    public function has($value)
    {
        return in_array($value, $this->items);
    }

    public function each(callable $callback)
    {
        foreach ($this->items as $item) {
            $callback($item);
        }
    }

    public function usort(callable $sort)
    {
        usort($this->items, $sort);
        return $this;
    }

    /**
     * @param $callback
     * @return $this
     */
    public function filter(callable $callback)
    {
        $this->items =  array_filter($this->items, $callback);
        return $this;
    }
}