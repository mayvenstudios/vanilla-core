<?php

namespace Core;

use Core\Query\Builder;

abstract class PostType {

    /**
     * @var array
     */
    protected static $instances = [];

    /**
     * Post Type name
     *
     * @var string
     */
    protected $name;

    /**
     * You can specify `singular`, `plural` and `slug` for a post type in $names array
     * The default values are generated from the post type name.
     *
     * @var array overrides
     */
    protected $names = [];

    /**
     * Define a template for index page
     *
     * @var string
     */
    protected $archiveTemplate;

    /**
     * List of available templates for a PostType
     *
     * @var array
     */
    protected $templates = [];

    /**
     * Post Type settings. For available options
     *
     * @see https://github.com/johnbillion/extended-cpts register_extended_post_type()
     * @see https://codex.wordpress.org/Function_Reference/register_post_type register_post_type()
     *
     * @return void|array
     */
    abstract protected function args();

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        $key = get_the_ID() ?: 'null';
        if (!isset(static::$instances[$key])) {
            static::$instances[$key] = new static();
        }
        return static::$instances[$key];
    }

    /**
     * Post id
     *
     * @return null|int
     */
    public function id()
    {
        return get_the_ID() ?: null;
    }

    /**
     * @see the_title();
     *
     * @param string $before
     * @param string $after
     *
     * @return string
     */
    public function title($before = '', $after = '')
    {
        return the_title($before, $after, false);
    }

    /**
     * @param null $more
     * @param bool $strip
     *
     * @return string
     */
    public function content($more = null, $strip = false)
    {
        ob_start();
        the_content($more, $strip);
        return ob_get_clean();
    }

    /**
     * Register post type
     */
    public function register()
    {
        if (!get_post_type_object($this->name())) {
            register_extended_post_type($this->name(), $this->arguments(), $this->names);
        } else {
            extend_post_type($this->name(), $this->arguments(), $this->names);
        }

        $this->registerTemplates();

        return $this;
    }

    protected function registerTemplates()
    {
        if(count($this->templates) === 0) {
            return null;
        }

        $themeTemplates = app()->getRegisteredTemplates();
        $existing = isset($themeTemplates[$this->name]) ? $themeTemplates[$this->name] : [];

        $postTemplates = $this->templates;
        unset($postTemplates['Default']);

        $themeTemplates[$this->name] = array_merge($existing, array_flip($postTemplates));


        wp_cache_set('post_templates-' . app()->cacheHash(), $themeTemplates, 'themes', 1800);
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Arguments for register_extended_post_type function
     *
     * @return array
     */
    public function arguments()
    {
        return array_merge($this->args() ?: [], [
            'className' => static::class,
            'defaultTemplate' => isset($this->templates['Default']) ? $this->templates['Default'] : null,
            'archiveTemplate' => $this->archiveTemplate
        ]);
    }

    /**
     * @return Builder
     */
    protected function newQuery()
    {
        return (new Builder())->type($this->name());
    }

    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * @return mixed
     */
    public static function all()
    {
        return static::query()->get();
    }

    public function __get($name)
    {
        return get_post()->$name;
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([static::query(), $name], $arguments);
    }
}