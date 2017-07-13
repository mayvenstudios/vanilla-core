<?php

namespace Core\Concerns;

trait MovesAdminBar {

    protected function moveAdminBar()
    {
        /**
         * No need to bother with admin bar positioning
         * if won't see it anyway
         */
        if (!is_admin_bar_showing()) {
            return;
        }

        switch ($this->config('admin_bar_position')) {
            case 'none': {
                add_filter('show_admin_bar', '__return_false');
                add_action('admin_head', [$this, 'hideToolbarFromDashboard']);
                break;
            }
            case 'bottom': {
                add_action('admin_head', [$this, 'moveBarToBottom']);
                add_action('wp_head', [$this, 'moveBarToBottom']);
                break;
            }
        }
    }

    public function moveBarToBottom()
    {
        echo '<script>document.getElementsByTagName("html")[0].className += " admin_bar_bottom"</script>';
        echo '
        <style type="text/css">
            html.admin_bar_bottom {margin-top: 0!important;}
            html.admin_bar_bottom.wp-toolbar {padding-top: 0!important;}
            * html.admin_bar_bottom body {margin: 0!important;}
            #wpadminbar {top: auto !important; bottom: 0px;}
            html.admin_bar_bottom #adminmenu {padding-bottom: 32px;}
            #wpadminbar .ab-sub-wrapper {top: auto !important; bottom: 32px;}
        </style>';
    }

    public function hideToolbarFromDashboard()
    {
        echo '<script>document.getElementsByTagName("html")[0].className += " admin_bar_bottom"</script>';
        echo '
        <style type="text/css">
            html.admin_bar_bottom.wp-toolbar {padding-top: 0!important;}
            #wpadminbar {display:none}
        </style>';
    }
}