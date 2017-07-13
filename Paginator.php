<?php

namespace Vanilla;

use Vanilla\Query\Builder;

class Paginator {

    /**
     * @var \WP_Query
     */
    protected $query;

    /**
     * Paginator constructor.
     *
     * @param \WP_Query $query
     */
    public function __construct(\WP_Query $query)
    {
        $this->query = $query;
    }

    /**
     * Get items
     *
     * @return \Generator
     */
    public function items()
    {
        return Builder::generator($this->query);
    }

    /**
     * Get current page number
     *
     * @return mixed
     */
    public function currentPage()
    {
        return max($this->query->get('paged', 1), 1);
    }

    /**
     * Get total number of pages
     *
     * @return mixed
     */
    public function pages()
    {
        return intval($this->query->max_num_pages);
    }

    /**
     * Check if next page exists
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return $this->currentPage() < $this->pages();
    }

    /**
     * next page url
     *
     * @return null|string
     */
    public function nextPageUrl()
    {
        if (!$this->hasNextPage()) {
            return null;
        }

        return $this->linkToPage($this->currentPage() + 1);
    }

    /**
     * Check if previous page exists
     *
     * @return bool
     */
    public function hasPreviousPage()
    {
        return $this->currentPage() > 1;
    }

    /**
     * previous page url
     *
     * @return null|string
     */
    public function previousPageUrl()
    {
        if (!$this->hasPreviousPage()) {
            return null;
        }

        return $this->linkToPage($this->currentPage() - 1);
    }

    /**
     * url to a given page
     *
     * @param $page integer page to build a link to
     *
     * @return string
     */
    public function linkToPage($page = 0)
    {
        $request = remove_query_arg('page_num');
        if ($page > 1) {
            $request = add_query_arg('page_num', $page);
        }

        $base = trailingslashit(get_bloginfo('url'));

        return $base . ltrim($request, '/');
    }
}