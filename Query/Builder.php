<?php

namespace Vanilla\Query;

use Vanilla\Paginator;

class Builder {

    use Concerns\QueriesTaxonomy;
    use Concerns\QueriesMeta;

    protected $args = [];

    public function buildArgs()
    {
        $metaQuery = $this->buildMetaQuery();
        if ($metaQuery) {
            $this->set('meta_query', $metaQuery);
        }

        $taxQuery = $this->buildTaxonomyQuery();
        if ($taxQuery) {
            $this->set('tax_query', $taxQuery);
        }

        if (!isset($this->args['_paged']) && isset($_REQUEST['page_num'])) {
            $this->page(intval($_REQUEST['page_num']));
        }

        if (!isset($this->args['posts_per_page'])) {
            $this->page(1);
            $this->perPage(9999);
        }

        return $this->args;
    }

    /**
     * Set custom node to the WP_Query $args
     *
     * @param array|string $key
     * @param null $value
     *
     * @return $this
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->args = array_merge($this->args, $key);
            return $this;
        }

        $this->args[$key] = $value;
        return $this;
    }

    /**
     * @param mixed $author
     *
     * @return Builder
     */
    public function author($author)
    {
        if (is_string($author)) {
            return $this->set('author_name', $author);
        }

        if (!is_array($author)) {
            $author = [$author];
        }
        return $this->set('author__in', $author);
    }

    /**
     * @param mixed $author
     *
     * @return Builder
     */
    public function authorNot($author)
    {
        if (!is_array($author)) {
            $author = [$author];
        }
        return $this->set('author__not_in', $author);
    }

    /**
     * @param mixed $post
     *
     * @return Builder
     */
    public function post($post)
    {
        if (!is_array($post)) {
            $post = [$post];
        }
        return $this->set('post__in', $post);
    }

    /**
     * @param mixed $post
     *
     * @return Builder
     */
    public function postNot($post)
    {
        if (!is_array($post)) {
            $post = [$post];
        }
        return $this->set('post__not_in', $post);
    }

    /**
     * @param $type
     *
     * @return Builder
     */
    public function type($type)
    {
        return $this->set('post_type', $type);
    }

    /**
     * @param array $value
     *
     * @return Builder
     */
    public function parentIn(array $value)
    {
        return $this->parent($value);
    }

    /**
     * @return Builder
     */
    public function noParent()
    {
        return $this->parent(0);
    }

    /**
     * @param $value
     *
     * @return Builder
     */
    public function parent($value)
    {
        if ($value === 0) {
            return $this->set('post_parent', 0);
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        return $this->set('post_parent__in', $value);
    }

    /**
     * @param $value
     *
     * @return Builder
     */
    public function slug($value)
    {
        return $this->set('name', $value);
    }

    /**
     * @param $value
     *
     * @return Builder
     */
    public function status($value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        return $this->set('post_status', $value);
    }

    /**
     * @param $keyword
     *
     * @return Builder
     */
    public function search($keyword)
    {
        return $this->set('s', $keyword);
    }

    /**
     * @param $orderBy
     * @param string $order
     *
     * @return Builder
     */
    public function orderBy($orderBy, $order = 'DESC')
    {
        if (is_array($orderBy)) {
            return $this->set('orderby', $orderBy);
        }

        if (in_array(strtoupper($orderBy), ['NONE', 'RAND'])) {
            return $this->set('orderby', $orderBy);
        }

        $words = explode(' ', $orderBy);
        $orderBy = [];
        foreach ($words as $word) {
            $orderBy[$word] = $order;
        }

        return $this->set('orderby', $orderBy);
    }

    /**
     * Ignore post stickiness
     *
     * @return Builder
     */
    public function ignoreStickyPosts()
    {
        return $this->set('ignore_sticky_posts', true);
    }

    /**
     * Return paginated query results
     *
     * @param null $val posts per page
     *
     * @return Paginator
     */
    public function paginate($val = null)
    {
        $val = $val ?: get_option('posts_per_page');
        return $this->perPage($val)->paginator();
    }

    /**
     * Set the number of posts per page for pagination
     *
     * @param $val
     *
     * @return Builder
     */
    public function perPage($val)
    {
        return $this->set('posts_per_page', $val);
    }

    /**
     * Set the page number for pagination.
     * The value set with this method will override
     * the page number from url
     *
     * This only does something if ->perPage() or ->paginate() are called
     *
     * @param $val
     *
     * @return Builder
     */
    public function page($val)
    {
        $this->set('_paged', $val);
        return $this->set('paged', $val);
    }

    /**
     * Set the offset.
     * This method works with WP pagination.
     *
     * @param $val
     *
     * @return Builder
     */
    public function offset($val)
    {
        $this->set('offset', $val);
        return $this->set('_offset', $val);
    }

    /**
     * @param $query
     *
     * @return Builder
     */
    public function dateRaw($query)
    {
        return $this->set('date_query', $query);
    }

    /**
     * @return \WP_Query
     */
    protected function createQuery()
    {
        return new \WP_Query($this->buildArgs());
    }

    /**
     * @return Paginator
     */
    public function paginator()
    {
        return new Paginator($this->createQuery());
    }

    /**
     * @return \Generator
     */
    public function get()
    {
        return static::generator($this->createQuery());
    }

    /**
     * @param \WP_Query $wpQuery
     *
     * @return \Generator
     */
    public static function generator(\WP_Query $wpQuery)
    {
        while ($wpQuery->have_posts()) {
            $wpQuery->the_post();
            yield post();
        }
        wp_reset_postdata();
    }
}