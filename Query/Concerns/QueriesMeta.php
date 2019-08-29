<?php
namespace Vanilla\Query\Concerns;

/**
 * Trait QueriesMeta
 *
 * @see https://codex.wordpress.org/Class_Reference/WP_Meta_Query
 * @package Vanilla\Query\Concerns
 */
trait QueriesMeta {

    protected $metaQueries = [];

    public function metaExists($meta)
    {
        return $this->metaRaw(['key' => $meta, 'compare' => 'EXISTS']);
    }

    public function metaNotExists($meta)
    {
        return $this->metaRaw([
            'key' => $meta,
            'compare' => 'NOT EXISTS',
            'value' => 'https://core.trac.wordpress.org/ticket/23268'
        ]);
    }

    public function metaIn($meta, array $value)
    {
        return $this->addMetaQuery('AND', $meta, 'IN', $value);
    }

    public function metaNotIn($meta, array $value)
    {
        return $this->addMetaQuery('AND', $meta, 'NOT IN', $value);
    }

    public function metaNot($meta, $value)
    {
        return $this->addMetaQuery('AND', $meta, '!=', $value);
    }

    public function meta($meta, $operator = null, $value = null)
    {
        return $this->addMetaQuery('AND', $meta, $operator, $value);
    }

    public function metaRaw($query)
    {
        return $this->pushMetaQuery('AND', $query);
    }

    public function orMetaNotIn($meta, array $value)
    {
        return $this->addMetaQuery('OR', $meta, 'NOT IN', $value);
    }

    public function orMetaNot($meta, $value)
    {
        return $this->addMetaQuery('OR', $meta, '!=', $value);
    }

    public function orMetaIn($meta, array $value)
    {
        return $this->addMetaQuery('OR', $meta, 'IN', $value);
    }

    public function orMeta($meta, $operator = null, $value = null)
    {
        return $this->addMetaQuery('OR', $meta, $operator, $value);
    }

    public function orMetaRaw($query)
    {
        return $this->pushMetaQuery('OR', $query);
    }

    protected function addMetaQuery($relation, $meta, $operator, $value = null, $type = null)
    {
        if($meta instanceof \Closure) {
            return $this->pushMetaQuery($relation, $meta(new static)->buildMetaQuery());
        }

        // use default operator when short call used
        if(is_null($value)) {
            $value = $operator;
            $operator = is_array($value) ? 'IN' : '=';
        }

        if(is_null($type)) {
            $type = (is_string($value) || is_array($value) && array_contains_type($value, 'string')) ?
                'CHAR' : 'NUMERIC';
        }

        return $this->pushMetaQuery($relation, [
            'key' => $meta,
            'type' => $type,
            'value' => $value,
            'compare' => strtoupper($operator)
        ]);
    }

    /**
     * @return array|mixed
     */
    public function buildMetaQuery()
    {
        if(count($this->metaQueries) === 0) {
            return [];
        }

        $queries = ['OR' => [], 'AND' => []];
        foreach ($this->metaQueries as $query) {
            $queries[$query['relation']][] = $query['query'];
        }

        $merged = [];
        if(count($queries['OR']) > 0) {
            switch (true) {
                case count($queries['AND']) === 0:
                    $merged = array_merge(['relation' => 'OR'], $queries['OR']);
                    break;
                case count($queries['AND']) === 1:
                    $merged = array_merge(['relation' => 'OR'], $queries['OR'], $queries['AND']);
                    break;
                case count($queries['AND']) > 1:
                    $andQueries = array_merge(['relation' => 'AND'], $queries['AND']);
                    $merged = array_merge(['relation' => 'OR'], $queries['OR'], [$andQueries]);
                    break;
            }
        } else {
            $merged = array_merge(['relation' => 'AND'], $queries['AND']);
        }

        return $this->nameQueries($merged);
    }

    protected function nameQueries($query) {
        $processed = [];
        foreach ($query as $key => $part) {
            if(is_numeric($key) && is_array($part)) {
                if(isset($part['key'])) {
                    $key = isset($processed[$part['key']]) ? $part['key'] . '.' . str_random(5) : $part['key'];
                    $processed[$key] = $part;
                } else {
                    $processed[] = $this->nameQueries($part);
                }
            } else {
                $processed[$key] = $part;
            }
        }
        return $processed;
    }

    protected function pushMetaQuery($relation, $query)
    {
        $this->metaQueries[] = [
            'relation' => $relation,
            'query' => $query
        ];
        return $this;
    }
}
