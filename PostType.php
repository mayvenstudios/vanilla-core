<?php

namespace Vanilla;

use Vanilla\Query\Builder;

abstract class PostType {

    /**
     * @var \WP_Post
     */
    protected $wp_post;

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
     * Define if the post type has archive page
     * If $hasArchivePage = false, $archiveTemplate property is ignored
     * Change in this property requires `wp flush-rewrites` call for the changes to be applied
     *
     * @see https://codex.wordpress.org/Function_Reference/register_post_type#has_archive
     *
     * @var bool
     */
    protected $hasArchivePage = true;

    /**
     * Archive page object used to access meta data and custom fields
     *
     * @var
     */
    protected $archivePage;

    /**
     * Define a template for index page
     *
     * @var string
     */
    protected $archiveTemplate = 'default';

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


    public function __construct(\WP_Post $wp_post = null)
    {
        $this->wp_post = $wp_post ?: get_post();
    }

    public static function find($id)
    {
        return new static(get_post($id));
    }

    public function field($name)
    {
        return get_field($name, $this->id);
    }

    /**
     * Post id
     *
     * @return null|int
     */
    public function id()
    {
        return object_get($this->wp_post, 'ID');
    }

    /**
     * @see the_title();
     *
     * @param string $before
     * @param string $after
     *
     * @return string
     */
    public function title()
    {
        return object_get($this->wp_post, 'post_title');
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

    public function slug()
    {
        return isset($this->names['slug']) ? $this->names['slug'] : str_slug(str_plural($this->name()));
    }

    /**
     * Register post type
     */
    public function register()
    {
        $existing = get_post_type_object($this->name());
        if (!$existing || !$existing->_builtin) {
            register_extended_post_type($this->name(), $this->arguments(), $this->names);
        } else {
            extend_post_type($this->name(), $this->arguments(), $this->names);
            add_rewrite_rule("{$this->slug()}/([^/]+)/?$", "index.php?&post_type={$this->name()}&name=\$matches[1]", 'top');
            add_rewrite_rule("{$this->slug()}/?$", "index.php?&post_type={$this->name()}", 'top');
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
        return $this->name ? : class_basename($this);
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
            'has_archive' => !! $this->hasArchivePage,
            'archiveTemplate' => $this->archiveTemplate,
        ], $this->args() ?: []);
    }

    protected function isExcludedFromSearch()
    {
        $blackList = app()->config('search.excluded_types', []);

        return in_array($this->name, $blackList) || in_array(get_class($this), $blackList);
    }

    public function hasTaxonomy($class)
    {
        return collect(wp_get_post_terms($this->id, (new $class)->name()));
    }

    public function archive()
    {
        if(!$this->archivePage) {
            $this->archivePage = app()->query()->type('page')->slug($this->slug())->take(1)->get()->pop();
        }
        return $this->archivePage;
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
        if(method_exists($this, $name)) {
            return $this->$name();
        }
        return object_get($this->wp_post, $name);
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