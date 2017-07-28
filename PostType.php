<?php

namespace Vanilla;

use Vanilla\Query\Builder;

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
     * Define if the post type have a single page or archive page.
     * If $hasPages = false, $templates and $archiveTemplate properties are ignored
     *
     * @see https://codex.wordpress.org/Function_Reference/register_post_type#publicly_queryable
     *
     * @var bool
     */
    protected $hasPublicPages = true;

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
     * List of custom fields
     *
     * @example ['sub_heading' => ['label' => 'Sub Heading', 'type' => 'text']]
     *
     * @var array
     */
    protected $fields = [];

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
        $this->registerCustomFields();

        return $this;
    }

    protected function registerTemplates()
    {
        if (count($this->templates) === 0) {
            return null;
        }

        $themeTemplates = app()->getRegisteredTemplates();
        $existing = isset($themeTemplates[$this->name]) ? $themeTemplates[$this->name] : [];

        $postTemplates = $this->templates;
        unset($postTemplates['Default']);

        $themeTemplates[$this->name] = array_merge($existing, array_flip($postTemplates));


        wp_cache_set('post_templates-' . app()->cacheHash(), $themeTemplates, 'themes', 1800);
    }

    protected function registerCustomFields()
    {
        if(!count($this->fields)) {
            return;
        }

        $fieldCreator = app()->fields()->forPostType($this);

        foreach ($this->fields as $key => $args) {
            $fieldCreator->add($key, $args);
        }

        $fieldCreator->create();
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function singularName()
    {
        return isset($this->names['singular']) ? $this->names['singular'] : ucfirst($this->name());
    }

    /**
     * Arguments for register_extended_post_type function
     *
     * @return array
     */
    public function arguments()
    {
        return array_merge([
            'exclude_from_search' => $this->isExcludedFromSearch(),
            'className' => static::class,
            'defaultTemplate' => isset($this->templates['Default']) ? $this->templates['Default'] : null,
            'publicly_queryable' => !! $this->hasPublicPages,
            'archiveTemplate' => $this->archiveTemplate
        ], $this->args() ?: []);
    }

    protected function isExcludedFromSearch()
    {
        $blackList = app()->config('search.excluded_types', []);

        return in_array($this->name, $blackList) || in_array(get_class($this), $blackList);
    }

    /**
     * @return Builder
     */
    protected function newQuery()
    {
        return (new Builder())->type($this->name());
    }

    /**
     * Create a query builder for a post type
     *
     * @return Builder
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * Get all posts of a given post type
     *
     * @return \Generator
     */
    public static function all()
    {
        return static::query()->get();
    }

    /**
     * If there is no custom parameter on a PostType
     * Object, user might want to access parameter from WP_Post object
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return get_post()->$name;
    }

    /**
     * This allows calling Query Builder methods statically on
     * PostType class. Post::all(), Page::paginate() etc.
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([static::query(), $name], $arguments);
    }
}