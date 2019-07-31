<?php

namespace Vanilla\Concerns;

trait HandlesEvents {
    public function registerEvents()
    {
        add_action('save_post', [$this, 'handlePostUpdated'], 10, 3);
    }

    public function handlePostUpdated($id, $post, $update)
    {
        $postType = get_post_type_object($post->post_type);
        $class = isset($postType->className) ? $postType->className : null;

        if($class) {
            $object = new $class($post);
            if(method_exists($object, 'updated')) $object->updated();
        }
    }
}