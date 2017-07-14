<?php
namespace Vanilla;

use Vanilla\View\Factory;
use Whoops\Handler\CallbackHandler;

abstract class Theme {

    use Concerns\Configurable,
        Concerns\ManagesEndpoints,
        Concerns\ManagesPostTypes,
        Concerns\ManagesTaxonomies,
        Concerns\ManagesCommands,
        Concerns\ManagesPaths,
        Concerns\GeneratesFaviconHtml,
        Concerns\IntegratesBlade,
        Concerns\MovesAdminBar;

    /** @var Factory */
    protected $view;

    /** @var static */
    public static $instance;

    /** @var array */
    protected $templates = [];

    /**
     * Method that contains all the custom initialization logic
     */
    abstract protected function startup();

    /**
     * Method that returns the array of data to be
     * available via window.Vanilla object
     *
     * @return mixed
     */
    abstract protected function vanillaObject();

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * Bootstrap function for the class.
     * Loads everything up based off of various parameters you can set.
     *
     * @param string $path root theme path
     */
    public function bootstrap($path = '')
    {
        $this->setPath($path);

        /** Register actions on wp hooks */
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
        add_action('wp_head', [$this, 'echoCustomHeader']);
        add_action('wp_footer', [$this, 'echoCustomFooter']);

        /** Register filters */
        add_filter('excerpt_more', [$this, 'substituteExcerpt']);

        /** Disable theme editor if needed */
        define('DISALLOW_FILE_EDIT', $this->config('disabled_theme_editor', false));
    }

    /**
     * Code to be executed on wp_init
     */
    public function init()
    {
        $this->registerErrorHandling();
        $this->loadConfiguration();
        $this->registerPostTypes();
        $this->registerEndpoints();
        $this->registerTaxonomies();
        $this->registerCommands();
        $this->initializeBlade();
        $this->removeJunk();
        $this->configureImages();
        $this->configureMenus();
        $this->configureSidebars();
        $this->requireExtensions();
        $this->moveAdminBar();
        $this->loadACF();
        $this->fixPaginationWithCustomOffset();
        $this->startup();
    }

    public function registerErrorHandling()
    {
        $whoops = new \Whoops\Run;
        if(defined('WP_DEBUG') && \WP_DEBUG) {
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        } else {
            $whoops->pushHandler(new CallbackHandler(function ($e) {
                die(view('path: '.__DIR__ . '/error.blade.php')->render());
            }));
        }

        $whoops->register();
    }

    /**
     * Register .js and .css assets
     */
    public function registerAssets()
    {
        $noJQuery = $this->config('include_jquery') === false;
        if ($noJQuery) {
            wp_deregister_script('jquery');
        }

        wp_enqueue_script('vendor', mix('/js/vendor.js'), [], null, true);
        wp_enqueue_script('script', mix('/js/app.js'), $noJQuery ? [] : ['jquery'], null, true);
        wp_enqueue_style('style', mix('/css/theme.css'));
    }

    /**
     * Echo custom header JS and CSS configured by admin and favicon <link> tags
     */
    public function echoCustomHeader()
    {
        echo "<script>window.Vanilla = " . json_encode($this->vanillaObject()) . "</script>";
        echo get_field('header_css_js_custom', 'option');
        echo $this->faviconHtml();
    }

    /**
     * Echo custom footer JS and CSS configured by admin
     */
    public function echoCustomFooter()
    {
        echo get_field('custom_js_footer', 'option');
    }

    /**
     * Clean up the_excerpt()
     */
    public function substituteExcerpt()
    {
        $excerpt = $this->config('excerpt_more_text', '');

        if ($this->config('excerpt_more_link')) {
            $excerpt = '<a href="' . get_permalink() . '">' . $excerpt . '</a>';
        }

        return $excerpt;
    }

    /**
     * Clean code = better code.
     */
    protected function removeJunk()
    {
        remove_action('template_redirect', 'wp_shortlink_header', 11);
        remove_action('wp_head', 'rel_canonical');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'index_rel_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'start_post_rel_link', 10);
        remove_action('wp_head', 'parent_post_rel_link', 10);
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
        add_filter('wp_headers', function ($headers) {
            unset($headers['X-Pingback']);
            return $headers;
        });
    }

    /**
     * Load Thumbnail Support and image sizes
     *
     * @see /config/images.php
     */
    protected function configureImages()
    {
        if ($this->config('load_thumbnail_support')) {
            add_theme_support('post-thumbnails');
        }

        foreach ($this->config('images', []) as $size) {
            add_image_size($size['name'], $size['width'], $size['height'], $size['crop']);
        }
    }

    /**
     * Loads the menus.
     * Menus are configured in the config/menus.php file
     *
     * @see /config/menus.php
     */
    protected function configureMenus()
    {
        if (is_array($this->config('menus'))) {
            add_theme_support('menus');
            register_nav_menus($this->config('menus'));
        }
    }

    /**
     * Loads the sidebars.
     * Sidebars are configured in the config/sidebars.php file
     *
     * @see /config/sidebars.php
     */
    protected function configureSidebars()
    {
        if (is_array($this->config('sidebars'))) {
            foreach ($this->config('sidebars') as $sidebar) {
                register_sidebar($sidebar);
            }
        }
    }

    /**
     * @return Factory
     */
    public function view()
    {
        if (is_null($this->view)) {
            $this->view = new Factory();
        }
        return $this->view;
    }

    /**
     * Require extensions files from app/extensions folder
     */
    public function requireExtensions()
    {
        $extensions = glob($this->extensionsPath('*'));
        foreach ($extensions as $path) {
            require_once $path;
        }
    }

    final private function __construct() {}

    final private function __wakeup() {}

    final private function __clone() {}


    public function parseTemplateDirectory($value, $post_id, $field)
    {
        $searchAndReplace = [
            '{IMAGEPATH}' => get_template_directory_uri() . '/public/images'
        ];

        foreach ($searchAndReplace as $search => $replace) {
            $value = str_replace($search, $replace, $value);
        }

        return $value;
    }

    public function myAcfSettingsPath($path)
    {
        return __DIR__ . '/lib/acf/';
    }

    public function myAcfSettingsDir($dir)
    {
        return __DIR__ . '/lib/acf/';
    }

    /**
     * Loads ACF if the plugin is not included.
     */
    protected function loadACF()
    {
        if (!class_exists('acf')) {
            add_filter('acf/settings/path', array($this, 'myAcfSettingsPath'));
            add_filter('acf/settings/dir', array($this, 'myAcfSettingsDir'));
            include_once('lib/acf/acf.php');

            if (WP_DEBUG == false && $this->config('force_enable_acf_option_panel') === false) {
                add_filter('acf/settings/show_admin', '__return_false');
            }
        }

        add_filter('acf/format_value', array($this, 'parseTemplateDirectory'), 10, 3);

        if (function_exists('acf_wpcli_register_groups')) {
            acf_wpcli_register_groups();
        }
    }

    /**
     * @see https://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination
     */
    protected function fixPaginationWithCustomOffset()
    {
        add_action('pre_get_posts', function (\WP_Query &$query) {
            global $paged;

            $pageNum = isset($query->query_vars['_paged']) ? $query->query_vars['_paged'] : $paged;

            if ($pageNum) {
                $query->set('paged', $pageNum);
                $query->is_paged = ($pageNum > 1);

                if ($customOffset = $query->query_vars['_offset']) {
                    $perPage = $query->query['posts_per_page'] ?: get_option('posts_per_page');
                    $trueOffset = $customOffset + (($pageNum - 1) * $perPage);
                    $query->set('offset', $trueOffset);
                    $query->query_vars['paged'] = $paged;
                }
            }
        }, 1);

        add_filter('found_posts', function ($found_posts, \WP_Query $query) {
            if ($query->is_home()) {
                return $found_posts - (isset($query->query_vars['_offset']) ? $query->query_vars['_offset'] : 0);
            }
            return $found_posts;
        }, 1, 2);
    }

    protected function debugMode()
    {
        return defined('WP_DEBUG') && WP_DEBUG === true;
    }

    protected function registerShortCode($tag, $handler)
    {
        add_shortcode($tag, $handler);
        return $this;
    }

    public function getRegisteredTemplates()
    {
        return wp_get_theme()->get_post_templates();
    }

    public function cacheHash()
    {
        $theme = wp_get_theme();
        return md5($theme->get_theme_root() . '/' . $theme->get_stylesheet());
    }
}