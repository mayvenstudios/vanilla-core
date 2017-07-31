<?php

namespace Vanilla\Concerns;

use Vanilla\Taxonomy;
use Illuminate\Support\Collection;

trait ManagesTaxonomies {

    /**
     * Register custom taxonomies
     */
    protected function registerTaxonomies()
    {
        $names = $this->taxonomies()->map(function (Taxonomy $taxonomy) {
            return $taxonomy->register()->name();
        });

        collect(['post_tag', 'category'])->each(function ($name) use ($names) {
            if (!$names->contains($name)) {
                unregister_taxonomy_forced($name);
            }
        });
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function taxonomies()
    {
        $files = collect(glob($this->appPath('Taxonomies/*.php')));
        return $files->reduce(function(Collection $classes, $path) {
            $className = "App\\Taxonomies\\" . rtrim(basename($path), '.php');
            if(class_exists($className)) {
                $classes->push(new $className);
            }
            return $classes;
        }, collect());
    }
}
