<?php

namespace Vanilla\Fields\Drivers;

use Vanilla\Fields\Contracts\Driver;
use Vanilla\PostType;

/**
 * Custom fields driver for free version of Acf plugin
 *
 * @package Vanilla\Fields\Drivers
 */
class Acf implements Driver {

    /**
     * The list of fields to be created
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Post type we're working with
     *
     * @var PostType
     */
    protected $postType;

    /**
     * Separate driver instance is constructed for
     * each PostType that have custom fields
     *
     * @param PostType $postType
     */
    public function __construct(PostType $postType)
    {
        $this->postType = $postType;
    }

    /**
     * Add a declaration of the field to be created
     *
     * @param $key
     * @param $args
     *
     * @return static
     */
    public function add($key, $args)
    {
        $this->fields[] = array_merge([
            'key' => $this->postType->name() . "_" . $key,
            'label' => array_get($args, 'label', ucfirst($key)),
            'name' => $key,
            'type' => array_get($args, 'type', 'text')
        ], $args);
        return $this;
    }

    /**
     * Create registered custom fields
     *
     * @return static
     */
    public function create()
    {
        if (!function_exists('register_field_group')) {
            return $this;
        }
        register_field_group([
            'id' => "acf_auto_" . $this->postType->name(),
            'title' => $this->postType->singularName(),
            'fields' => $this->fields,
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => $this->postType->name(),
                        'order_no' => 0,
                        'group_no' => 0,
                    ]
                ]
            ],
            'options' => [
                'position' => 'normal',
                'layout' => 'no_box',
                'hide_on_screen' => [],
            ],
            'menu_order' => 0,
        ]);

        return $this;
    }
}