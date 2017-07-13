<?php

namespace Vanilla\Query\Concerns;

trait QueriesTaxonomy {

    protected $taxQueries = [];

    public function taxExists($taxonomy)
    {
        return $this->taxonomyExists($taxonomy);
    }

    public function taxonomyExists($taxonomy)
    {
        return $this->taxonomyRaw(['taxonomy' => $taxonomy, 'operator' => 'EXISTS']);
    }

    public function taxNotExists($taxonomy)
    {
        return $this->taxNotExists($taxonomy);
    }

    public function taxonomyNotExists($taxonomy)
    {
        return $this->taxonomyRaw(['taxonomy' => $taxonomy, 'operator' => 'NOT EXISTS']);
    }

    public function taxIn($taxonomy, array $value)
    {
        return $this->taxonomyIn($taxonomy, $value);
    }

    public function taxonomyIn($taxonomy, array $value)
    {
        return $this->addTaxonomyQuery('AND', $taxonomy, 'IN', $value);
    }

    public function taxNotIn($taxonomy, array $value)
    {
        return $this->taxonomyNotIn($taxonomy, $value);
    }

    public function taxonomyNotIn($taxonomy, array $value)
    {
        return $this->addTaxonomyQuery('AND', $taxonomy, 'NOT IN', $value);
    }

    public function taxNot($taxonomy, $value)
    {
        return $this->taxonomyNot($taxonomy, $value);
    }

    public function taxonomyNot($taxonomy, $value)
    {
        return $this->addTaxonomyQuery('AND', $taxonomy, '!=', $value);
    }

    public function tax($taxonomy, $operator = null, $value = null)
    {
        return $this->taxonomy($taxonomy, $operator, $value);
    }

    public function taxonomy($taxonomy, $operator = null, $value = null)
    {
        return $this->addTaxonomyQuery('AND', $taxonomy, $operator, $value);
    }

    public function taxRaw($query)
    {
        return $this->taxonomyRaw($query);
    }

    public function taxonomyRaw($query)
    {
        return $this->pushTaxonomyQuery('AND', $query);
    }

    public function orTaxExists($taxonomy)
    {
        return $this->orTaxonomyExists($taxonomy);
    }

    public function orTaxonomyExists($taxonomy)
    {
        return $this->orTaxonomyRaw(['taxonomy' => $taxonomy, 'operator' => 'EXISTS']);
    }

    public function orTaxNotExists($taxonomy)
    {
        return $this->orTaxNotExists($taxonomy);
    }

    public function orTaxonomyNotExists($taxonomy)
    {
        return $this->orTaxonomyRaw(['taxonomy' => $taxonomy, 'operator' => 'NOT EXISTS']);
    }

    public function orTaxNotIn($taxonomy, array $value)
    {
        return $this->orTaxonomyNotIn($taxonomy, $value);
    }

    public function orTaxonomyNotIn($taxonomy, array $value)
    {
        return $this->addTaxonomyQuery('OR', $taxonomy, 'NOT IN', $value);
    }

    public function orTaxNot($taxonomy, $value)
    {
        return $this->orTaxonomyNot($taxonomy, $value);
    }

    public function orTaxonomyNot($taxonomy, $value)
    {
        return $this->addTaxonomyQuery('OR', $taxonomy, '!=', $value);
    }

    public function orTaxIn($taxonomy, array $value)
    {
        return $this->orTaxonomyIn($taxonomy, $value);
    }

    public function orTaxonomyIn($taxonomy, array $value)
    {
        return $this->addTaxonomyQuery('OR', $taxonomy, 'IN', $value);
    }

    public function orTax($taxonomy, $operator = null, $value = null)
    {
        return $this->orTaxonomy($taxonomy, $operator, $value);
    }

    public function orTaxonomy($taxonomy, $operator = null, $value = null)
    {
        return $this->addTaxonomyQuery('OR', $taxonomy, $operator, $value);
    }

    public function orTaxRaw($query)
    {
        return $this->orTaxonomyRaw($query);
    }

    public function orTaxonomyRaw($query)
    {
        return $this->pushTaxonomyQuery('OR', $query);
    }

    /**
     * @return array|mixed
     */
    public function buildTaxonomyQuery()
    {
        if (count($this->taxQueries) === 0) {
            return [];
        }

        $queries = ['OR' => [], 'AND' => []];
        foreach ($this->taxQueries as $query) {
            $queries[$query['relation']][] = $query['query'];
        }

        if (count($queries['OR']) > 0) {
            switch (true) {
                case count($queries['AND']) === 0:
                    return array_merge(['relation' => 'OR'], $queries['OR']);
                    break;
                case count($queries['AND']) === 1:
                    return array_merge(['relation' => 'OR'], $queries['OR'], $queries['AND']);
                    break;
                case count($queries['AND']) > 1:
                    $andQueries = array_merge(['relation' => 'AND'], $queries['AND']);
                    return array_merge(['relation' => 'OR'], $queries['OR'], [$andQueries]);
                    break;
            }
        }

        return array_merge(['relation' => 'AND'], $queries['AND']);
    }

    protected function addTaxonomyQuery($relation, $taxonomy, $operator, $value = null)
    {
        if (is_callable($taxonomy)) {
            return $this->pushTaxonomyQuery($relation, $taxonomy(new static)->buildTaxonomyQuery());
        }

        list($value, $operator) = is_null($value) ? [$operator, 'IN'] : [$value, $operator];

        $value = is_array($value) ? $value : [$value];
        $field = array_contains_type($value, 'string') ? 'slug' : 'term_id';

        return $this->pushTaxonomyQuery($relation, [
            'taxonomy' => $taxonomy,
            'field' => $field,
            'terms' => $value,
            'operator' => strtoupper($operator)
        ]);
    }

    protected function pushTaxonomyQuery($relation, $query)
    {
        $this->taxQueries[] = [
            'relation' => $relation,
            'query' => $query
        ];
        return $this;
    }

}
