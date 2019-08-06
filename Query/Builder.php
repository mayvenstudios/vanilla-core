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
            $this->setParameter('meta_query', $metaQuery);
        }

        $taxQuery = $this->buildTaxonomyQuery();
        if ($taxQuery) {
            $this->setParameter('tax_query', $taxQuery);
        }

        if (!isset($this->args['_paged']) && isset($_REQUEST['page_num'])) {
            $this->page(intval($_REQUEST['page_num']));
        }

        if (!isset($this->args['posts_per_page'])) {
            $this->page(1);
            $this->perPage(-1);
        }

        if(!isset($this->args['post_type'])) {
            $this->args['post_type'] = app()->postTypeNames();
        }

        if(isset($this->args['author__in'])) {
            $this->args['author__in'] = array_map(function($element) {
                if(is_string($element)) {
                    return get_user_by('login', $element)->ID;
                }
                return $element;
            }, $this->args['author__in']);
        }

        if(isset($this->args['author__not_in'])) {
            $this->args['author__not_in'] = array_map(function($element) {
                if(is_string($element)) {
                    return get_user_by('login', $element)->ID;
                }
                return $element;
            }, $this->args['author__not_in']);
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
    public function setParameter($key, $value = null)
    {
        if (is_array($key)) {
            $this->args = array_merge($this->args, $key);
            return $this;
        }

        $this->args[$key] = $value;
        return $this;
    }

    public function getParameter($key, $default = null)
    {
        if(isset($this->args[$key])) {
            return $this->args[$key];
        }
        return $default;
    }

    /**
     * @param mixed $author
     *
     * @return Builder
     */
    public function author($author)
    {
        if (!is_array($author)) {
            $author = [$author];
        }

        return $this->setParameter('author__in', $author);
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

        return $this->setParameter('author__not_in', $author);
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
        return $this->setParameter('post__in', $post);
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
        return $this->setParameter('post__not_in', $post);
    }

    /**
     * @param $type
     *
     * @return Builder
     */
    public function type($type)
    {
        if(!is_array($type)) {
            $type = [$type];
        }

        $existing = $this->getParameter('post_type');
        if($existing) {
            $type = array_unique(array_merge($existing, $type));
        }

        return $this->setParameter('post_type', $type);
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
            return $this->setParameter('post_parent', 0);
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        return $this->setParameter('post_parent__in', $value);
    }

    /**
     * @param $value
     *
     * @return Builder
     */
    public function slug($value)
    {
        return $this->setParameter('name', $value);
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
        return $this->setParameter('post_status', $value);
    }

    /**
     * @param $keyword
     *
     * @return Builder
     */
    public function search($keyword)
    {
        return $this->setParameter('s', $keyword);
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
            return $this->setParameter('orderby', $orderBy);
        }

        if (in_array(strtoupper($orderBy), ['NONE', 'RAND'])) {
            return $this->setParameter('orderby', $orderBy);
        }

        $words = explode(' ', $orderBy);
        $orderBy = [];
        foreach ($words as $word) {
            $orderBy[$word] = $order;
        }

        return $this->setParameter('orderby', $orderBy);
    }

    /**
     * Ignore post stickiness
     *
     * @return Builder
     */
    public function ignoreStickyPosts()
    {
        return $this->setParameter('ignore_sticky_posts', true);
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

    public function take($val)
    {
        return $this->limit($val);
    }

    /**
     * Limit the number of returned posts
     *
     * @param $val
     *
     * @return Builder
     */
    public function limit($val)
    {
        return $this->perPage($val);
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
        return $this->setParameter('posts_per_page', $val);
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
        $this->setParameter('_paged', $val);
        return $this->setParameter('paged', $val);
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
        $this->setParameter('offset', $val);
        return $this->setParameter('_offset', $val);
    }

    /**
     * @param $query
     *
     * @return Builder
     */
    public function dateRaw($query)
    {
        return $this->setParameter('date_query', $query);
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
     * @return integer
     */
    public function count()
    {
        return $this->createQuery()->post_count;
    }

    /**
     * @param \WP_Query $wpQuery
     *
     * @return \Generator
     */
    public static function generator(\WP_Query $wpQuery)
    {
        $posts = collect();
        while ($wpQuery->have_posts()) {
            $wpQuery->the_post();
            $posts->push(post());
        }
        wp_reset_postdata();
        return $posts;
    }
}