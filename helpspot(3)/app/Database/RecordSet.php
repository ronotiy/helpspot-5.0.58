<?php

namespace HS\Database;

class RecordSet
{
    protected $records = [];

    /**
     * RecordSet constructor.
     * @param $records
     */
    public function __construct($records)
    {
        $this->records = $records;

        // Make sure the pointer is at the beginning
        $this->MoveFirst();
    }

    /**
     * @return array|bool
     */
    public function FetchRow()
    {
        // Records are at an end so return false to stop the
        // while loop this method is normally used in
        if (current($this->records) === false) {
            return false;
        }

        // Grab the current row and cast to an array which
        // is what all of HelpSpot expects
        $current = (array) current($this->records);

        // Advance the array pointer so next time this is called
        // the next element will be returned.
        next($this->records);

        return $current;
    }

    public function CurrentRow()
    {

        // Grab the current row and cast to an array which
        // is what all of HelpSpot expects
        $current = (array) current($this->records);

        return $current;
    }

    /**
     * @return int
     */
    public function RecordCount()
    {
        $count = count($this->records);

        // Count appears to rest the pointer to the end of the array so reset it
        $this->MoveFirst();

        return $count;
    }

    /**
     * Reset array pointer to beginning of records array.
     */
    public function MoveFirst()
    {
        reset($this->records);
    }

    public function MoveLast()
    {
        end($this->records);
    }

    /**
     * Reset array pointer to the end of records array.
     */
    public function MoveEnd()
    {
        end($this->records);
        next($this->records);
    }

    /**
     * Set array pointer somewhere within the records array.
     * @param int $position
     */
    public function Move($position = 0)
    {
        $this->MoveFirst();

        for ($i = 0; $i < $position; $i++) {
            next($this->records);
        }
    }

    /**
     * Slice record array.
     * @param $rows
     * @param $offset
     * @return array
     */
    public function Slice($rows, $offset)
    {
        return new static(array_slice($this->records, $offset, $rows));
    }

    /**
     * Convert record set from array of objects to array of arrays.
     * @return $this
     */
    public function ToArray()
    {
        array_walk($this->records, function (&$item) {
            $item = (array) $item;
        });

        return $this;
    }

    /**
     * Return the records array.
     * @return mixed
     */
    public function Records()
    {
        return $this->records;
    }

    /**
     * Filter records, allowing us to remove some records
     * This DOES affect the records of the ResultSet.
     * @param callable $callback
     * @return $this
     */
    public function Filter(callable $callback)
    {
        $this->records = array_filter($this->records, $callback, ARRAY_FILTER_USE_BOTH);

        return $this;
    }

    /**
     * Ability to change/modify each record
     * This DOES affect the records of the ResultSet.
     * @param callable $callback
     * @return $this
     */
    public function Walk(callable $callback)
    {
        array_walk($this->records, $callback);

        return $this;
    }

    /**
     * Multisort record set.
     * This DOES affect the records of the ResultSet
     * TODO: Abstracted from hs_filter class but I don't
     *       think this is ever actually called in "modern" HelpSpot.
     * @param $order
     * @param $sort
     * @return $this
     */
    public function Multisort($order, $sort)
    {
        // TODO: Abstracted out of hs_filter, but I'm not sure
        //       this parameter order is even correct?! (taken as-is from hs_filter)
        array_multisort($order, $sort, $this->records);

        return $this;
    }

    /**
     * Map records, returning a new array of items.
     * This does NOT affect the records directly.
     * @param callable $callback
     * @return array|bool|false
     */
    public function Map(callable $callback)
    {
        $keys = array_keys($this->items);

        $items = array_map($callback, $this->items, $keys);

        return array_combine($keys, $items);
    }
}
