<?php

namespace Vanilla\Concerns;

trait IntegratesBlade {

    public function initializeBlade()
    {
        if($this->debugMode()) {
            $this->clearCompiled();
        }

        /**
         * This filter hook is executed immediately before WordPress includes the predetermined template file.
         * This can be used to override WordPress's default template behavior.
         *
         * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/template_include
         */
        add_action('template_include', [$this, 'overrideTemplate']);
    }

    public function overrideTemplate()
    {
        $template = str_replace('.', '/', $this->decideTemplate()) . '.blade.php';

        $compiled = $this->compiledPath(sha1("WP: " . $template) . '.php');
        file_put_contents($compiled, "<?php echo app()->view()->make('path: {$template}')->render(); ?>");
        return $compiled;
    }

    protected function decideTemplate()
    {
        $postType = get_post_type_object(get_post_type());
        if($postType && $postType->_builtin && is_archive() && !$postType->_has_archive) {
            global $wp_query;
            $wp_query->set_404();
        }

        if(is_404()) {
            return app()->viewsPath($this->config('not_found_template', 'default'));
        }

        if(is_search()) {
            return app()->viewsPath($this->config('search.search_page_template', 'default'));
        }

        if(is_tax() || is_tag() || is_category()) {
            $tax = get_taxonomy(get_queried_object()->taxonomy);
            $view = $tax->archiveTemplate ?: 'default';
            return app()->viewsPath($view);
        }

        $predefinedTemplate = get_post()->page_template;
        if (!is_archive() && $predefinedTemplate && $predefinedTemplate !== 'default') {
            return app()->viewsPath($predefinedTemplate);
        }

        if ($postType) {
            $viewType = is_archive() ? 'archiveTemplate' : 'defaultTemplate';
            if ($view = $postType->$viewType) {
                return app()->viewsPath($view);
            }
        }

        return app()->viewsPath('default');
    }

    public function clearCompiled()
    {
        $files = glob(app()->compiledPath('*'));
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}