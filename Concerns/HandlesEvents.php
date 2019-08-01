<?php

namespace Vanilla\Concerns;

trait HandlesEvents
{
    public function registerEvents()
    {
        add_action('post_updated', [$this, 'handlePostUpdated'], 10, 3);
        add_action('delete_post', [$this, 'handlePostDeleted']);
        add_action('wp_trash_post', [$this, 'handlePostDeleted']);


    }

    public function handlePostUpdated($id, $post, $update)
    {
        if($post->post_status === 'trash') return;

        $postType = get_post_type_object($post->post_type);
        $class = isset($postType->className) ? $postType->className : null;

        if ($class) {
            $object = new $class($post);
            if (method_exists($object, 'updated')) $object->updated();
        }
    }

    public function handlePostDeleted($id)
    {
        $post = get_post($id);
        $postType = get_post_type_object($post->post_type);
        $class = isset($postType->className) ? $postType->className : null;

        if ($class) {
            $object = new $class($post);
            if (method_exists($object, 'deleted')) $object->deleted();
        }
    }
}