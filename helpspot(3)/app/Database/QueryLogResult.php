<?php

namespace HS\Database;


class QueryLogResult
{
    /**
     * @var array
     */
    protected $queryLog;

    public static function fullSql($queryLog)
    {
        return (new static($queryLog))->generateSql();
    }

    /**
     * QueryLogResult constructor.
     * @param array $queryLog A single Laravel query result array: ['query' => Illuminate\Database\Query\Expression, 'bindings' => array, 'time' => float]
     */
    public function __construct(array $queryLog)
    {
        $this->queryLog = $queryLog;
    }

    public function generateSql()
    {
        $query = (string)$this->queryLog['query'];

        foreach($this->queryLog['bindings'] as $key => $value) {
            $query = preg_replace('/\?/', $this->quoteBindingValue($value), $query, 1);
        }

        return $query;
    }

    protected function quoteBindingValue($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        return '"'.$value.'"';
    }
}
