<?php

namespace Vanilla;

use Vanilla\Fields;
use Vanilla\Query\Builder;
use Vanilla\View;

abstract class Theme {

    use Concerns\Configurable,
        Concerns\ManagesEndpoints,
        Concerns\ManagesPostTypes,
        Concerns\ManagesTaxonomies,
        Concerns\ManagesCommands,
        Concerns\ManagesPaths,
        Concerns\GeneratesFaviconHtml,
        Concerns\IntegratesBlade,
        Concerns\MovesAdminBar,
        Concerns\ManagesErrorReporting,
        Concerns\ManagesLogging;

    /** @var View\Factory */
    protected $view;

    /** @var Fields\Factory */
    protected $fields;

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

        $this->loadConfiguration();
        $this->registerErrorHandling();

        /** Register actions on wp hooks */
        add_action('init', [$this, 'init']);
        add_action('phpmailer_init', [$this, 'configureSmtp']);
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
        $this->rurnOffPageForPosts();
        $this->updateSearchSlug();
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
        $this->fixPaginationWithCustomOffset();
        $this->startup();
    }

    protected function rurnOffPageForPosts()
    {
        add_filter("pre_option_page_for_posts", function () {
            return null;
        });
        add_action('admin_head', function () {
            echo '<style type="text/css">#front-static-pages label[for="page_for_posts"] {display:none;}</style>';
        });
    }

    protected function updateSearchSlug()
    {
        global $wp_rewrite;
        $wp_rewrite->search_base = $this->config('search.slug', 'search');
    }

    public function configureSmtp($mailer)
    {
        $config = $this->config('email');
        if(!$config) return;

        $mailer->isSMTP();
        $mailer->SMTPAuth = true;
        $mailer->Host = $config['smtp_host'];
        $mailer->Port = $config['smtp_port'];
        $mailer->Username = $config['smtp_username'];
        $mailer->Password = $config['smtp_password'];
        $mailer->From = $config['from'];
        $mailer->FromName = $config['from_name'];
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
        wp_enqueue_style('style', mix('/css/theme.css'), [], null);
    }

    /**
     * Echo custom header JS and CSS configured by admin and favicon <link> tags
     */
    public function echoCustomHeader()
    {
        echo "<script>window.Vanilla = " . json_encode($this->vanillaObject()) . "</script>";
        echo app()->fields()->get('header_css_js_custom', 'option');
        echo $this->faviconHtml();
    }

    /**
     * Echo custom footer JS and CSS configured by admin
     */
    public function echoCustomFooter()
    {
        echo app()->fields()->get('custom_js_footer', 'option');
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
     * @return View\Factory
     */
    public function view()
    {
        if (is_null($this->view)) {
            $this->view = new View\Factory();
        }
        return $this->view;
    }

    /**
     * @return Fields\Factory
     */
    public function fields()
    {
        if (is_null($this->fields)) {
            $this->fields = new Fields\Factory();
        }
        return $this->fields;
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

                if (isset($query->query_vars['_offset'])) {
                    $customOffset = $query->query_vars['_offset'];
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

    public function query()
    {
        return new Builder();
    }
}