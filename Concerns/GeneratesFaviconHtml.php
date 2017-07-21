<?php

namespace Vanilla\Concerns;

trait GeneratesFaviconHtml {
    /**
     * Creates the html <link> tags for favicons
     *
     * @return string;
     */
    protected function faviconHtml()
    {
        $html = '';
        $faviconPath = app()->fields()->get('favicon', 'option');
        if ($faviconPath) {
            $html .= "<link rel='shortcut icon' href='{$faviconPath}' type='image/x-icon'>\n";
        }

        $otherIcons = app()->fields()->get('other_icons', 'option');
        if ($otherIcons) {
            foreach ($otherIcons as $icon) {
                $html .= "<link rel='apple-touch-icon' type='image/png' sizes='{$icon['size']}' href='{$icon['image']}'>\n";
            }
        }
        return $html;
    }
}