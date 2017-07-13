<?php

namespace Core\Concerns;

use Core\PostType;
use Illuminate\Support\Collection;

trait ManagesPostTypes {

    /**
     * Register custom post types.
     */
    protected function registerPostTypes()
    {
        $names = $this->postTypes()->map(function (PostType $postType) {
            return $postType->register()->name();
        });

        collect(['post', 'page', 'attachment'])->each(function ($name) use ($names) {
            if (!$names->contains($name)) {
                unregister_post_type_forced($name);
            }
        });
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function postTypes()
    {
        $files = collect(glob($this->appPath('PostTypes/*.php')));
        return $files->reduce(function(Collection $classes, $path) {
            $className = "App\\PostTypes\\" . rtrim(basename($path), '.php');
            if(class_exists($className)) {
                $classes->push(new $className);
            }
            return $classes;
        }, collect());
    }
}