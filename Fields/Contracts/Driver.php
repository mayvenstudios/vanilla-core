<?php
namespace Vanilla\Fields\Contracts;

use Vanilla\PostType;

/**
 * Interface Driver
 *
 * @package Vanilla\Fields\Contracts
 */
interface Driver {

    /**
     * Separate driver instance is constructed for
     * each PostType that have custom fields
     *
     * @param PostType $postType
     */
    public function __construct(PostType $postType);

    /**
     * Add a declaration of the field to be created
     *
     * @param $key
     * @param $args
     *
     * @return static
     */
    public function add($key, $args);

    /**
     * Create registered custom fields
     *
     * @return static
     */
    public function create();
}