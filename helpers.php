<?php

if (!function_exists('asset')) {
    /**
     * Generate an asset path for the file.
     *
     * @param string $path
     *
     * @return string
     */
    function asset($path)
    {
        return get_template_directory_uri() . "/assets/{$path}";
    }
}

if (!function_exists('asset_image')) {
    /**
     * Wrapper for asset method that returns files from "images" folder
     *
     * @param $path - name of the image inside asset/images/ folder
     *
     * @return string full image uri path
     */
    function asset_image($path)
    {
        return asset("images/$path");
    }
}

if (!function_exists('mix')) {

    /**
     * Get the path to a versioned Mix file.
     *
     * @param  string $path
     *
     * @return string
     * @throws \Exception
     */
    function mix($path)
    {
        static $manifest;

        $path = '/' . ltrim($path, '/');

        if (!$manifest) {
            if (!file_exists($manifestPath = app()->assetsPath('compiled/mix-manifest.json'))) {
                throw new Exception('The Mix manifest does not exist.');
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);
        }

        if (!array_key_exists($path, $manifest)) {
            throw new Exception(
                "Unable to locate Mix file: {$path}. Please check your " .
                'webpack.mix.js output paths and try again.'
            );
        }

        $compiled = "compiled/" . ltrim($manifest[$path], '/');
        return asset($compiled);
    }
}

if (!function_exists('unregister_post_type_forced')) {

    /**
     * Unregister post type.
     * Allows removing built in types
     *
     * @param $type
     */
    function unregister_post_type_forced($type)
    {
        global $wp_post_types;
        $post_type_object = get_post_type_object($type);
        $post_type_object->remove_supports();
        $post_type_object->remove_rewrite_rules();
        $post_type_object->unregister_meta_boxes();
        $post_type_object->remove_hooks();
        $post_type_object->unregister_taxonomies();

        unset($wp_post_types[$type]);
    }
}

if (!function_exists('unregister_taxonomy_forced')) {

    /**
     * Unregister taxonomy.
     * Allows removing built in taxonomies
     *
     * @param $taxonomy
     */
    function unregister_taxonomy_forced($taxonomy)
    {
        global $wp_taxonomies;

        $taxonomy_object = get_taxonomy($taxonomy);
        $taxonomy_object->remove_rewrite_rules();
        $taxonomy_object->remove_hooks();

        unset($wp_taxonomies[$taxonomy]);

        do_action('unregistered_taxonomy', $taxonomy);
    }
}

if (!function_exists('extend_post_type')) {
    /**
     * Extend existing post type.
     *
     * @param $type
     * @param $args
     * @param $names
     */
    function extend_post_type($type, $args, $names)
    {
        global $wp_post_types;

        /*
         * register_extended_post_type will update the labels
         * of an existing post type, but won't add any data to
         * the post type object
         */
        register_extended_post_type($type, $args, $names);

        /*
         * We don't want to mess up built in post types
         * so we only apply some of the arguments provided
         */
        $object = get_post_type_object($type);
        $whiteList = [
            'archiveTemplate', 'defaultTemplate', 'className',
            'exclude_from_search', 'has_archive'
        ];
        foreach ($whiteList as $key) {
            if(isset($args[$key])) {
                $object->$key = $args[$key];
            }
        }

        if($object->_builtin) {
            $object->_has_archive = $object->has_archive;
            $object->has_archive = true;
        }

        $wp_post_types[$type] = $object;
    }
}

if (!function_exists('app')) {

    /**
     * Get the Theme instance
     *
     * @return \App\Theme
     */
    function app()
    {
        return App\Theme::getInstance();
    }
}

if (!function_exists('post')) {

    /**
     * Get the *current* custom post type object
     *
     * @return null|\Vanilla\PostType
     */
    function post()
    {
        setup_postdata(get_post());

        $postType = get_post_type_object(get_post_type());
        $class = isset($postType->className) ? $postType->className : null;

        return $class ? new $class(get_post()) : null;
    }
}

if (!function_exists('archive')) {

    /**
     * Get the *current* custom post type object
     *
     * @param null $className
     * @return null|\Vanilla\PostType
     */
    function archive($className = null)
    {
        $post = $className ? new $className() : post();
        return $post ? $post->archive() : null;
    }
}

if (!function_exists('view')) {

    /**
     * @param $view
     * @param array $data
     *
     * @return \Vanilla\View\View
     */
    function view($view, $data = [])
    {
        return app()->view()->make($view, $data);
    }
}

if (!function_exists('array_contains_type')) {
    /**
     * Check if array contains a
     *
     * @param array $array
     * @param $type
     *
     * @return bool
     */
    function array_contains_type(array $array, $type)
    {
        foreach ($array as $item) {
            if (gettype($item) === $type) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('nl2br')) {

    /**
     * Substitute newlines with <br /> tag
     *
     * @param $input
     *
     * @return mixed
     */
    function nl2br($input)
    {
        return preg_replace("/(\r\n|\n|\r)/", "<br />", $input);
    }
}

if (!function_exists('to_sentence')) {

    /**
     * Convert a string to space separated words
     * with capitalized first letter each
     *
     * @param $string
     *
     * @return string
     */
    function to_sentence($string)
    {
        return join(' ', array_map(function ($word) {
            return ucfirst($word);
        }, explode('_', snake_case($string))));
    }
}

if (!function_exists('excerpt')) {

    /**
     *
     * @param int $length length in characters
     * @param string $class classes to be added to the `a` tag
     *
     * @return bool|mixed|string
     */
    function excerpt($length = 500, $class = '')
    {
        $post = get_post();
        if (empty($post)) {
            return '';
        }

        if (post_password_required($post)) {
            return __('There is no excerpt because this is a protected post.');
        }

        $excerpt = $post->post_excerpt;
        if ($excerpt === '') {
            $text = get_the_content('');
            $text = strip_shortcodes($text);
            $text = strip_tags($text);
            $text = str_replace(']]>', ']]&gt;', $text);
            $excerpt = trim(substr($text, 0, $length));
        }

        $more = app()->config('excerpt_more_text', '');
        if (app()->config('excerpt_more_link')) {
            $link = get_permalink();
            $more = "<a href='{$link}' class='{$class}'>$more</a>";
        }

        return $excerpt . " " . $more;
    }
}

if (!function_exists('log_info')) {

    /**
     * Add an info line to the logfile
     *
     * @param $message
     */
    function log_info($message) {
        app()->log()->info($message);
    }
}

if (!function_exists('log_error')) {

    /**
     * Add an error line to the logfile
     *
     * @param $message
     */
    function log_error($message) {
        app()->log()->error($message);
    }
}

if (!function_exists('query')) {

    /**
     * @return \Vanilla\Query\Builder
     */
    function query() {
        return app()->query();
    }
}

if (!function_exists('uploads_path')) {

    /**
     * Returns a path in the `uploads` folder
     *
     * @param $dir
     * @return string path
     */
    function uploads_path($dir = null) {
        $path = join(DIRECTORY_SEPARATOR, [wp_upload_dir()['path'], $dir]);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }
}
