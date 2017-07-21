<?php
namespace Vanilla\Fields;

use Vanilla\Fields\Drivers\Acf;
use Vanilla\PostType;

class Factory {

    protected $postType;

    protected $drivers = [];

    public function forPostType(PostType $postType)
    {
        if(!isset($this->drivers[$postType->name()])) {
            $this->drivers[$postType->name()] = $this->createDriver($postType);
        }
        return $this->drivers[$postType->name()];
    }

    public function get($key, $post = false, $format = true)
    {
        return function_exists('get_field') ? get_field($key, $post, $format) : null;
    }

    protected function createDriver(PostType $postType)
    {
        $driver = app()->config('custom_fields_driver', Acf::class);
        return new $driver($postType);
    }
}