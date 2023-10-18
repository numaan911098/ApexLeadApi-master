<?php

namespace App\Services;

use Log;

class LandingPageService
{
    public function pageScripts($page, $position)
    {
        foreach ($page->config['tracking']['scripts'] as $script) {
            if ($position === $script['position']) {
                $this->insertScript($script);
            } elseif ($position === $script['position']) {
                $this->insertScript($script);
            } elseif ($position === $script['position']) {
                $this->insertScript($script);
            } elseif ($position === $script['position']) {
                $this->insertScript($script);
            }
        }
    }

    protected function insertScript($script)
    {
        if ($script['tag'] === 'script_tag') {
            if (!empty($script['content'])) {
                echo '<script type="text/javascript">' . $script['content'] . '</script>';
            }
        } elseif ($script['tag'] === 'script_url') {
            $async = $script['async'] ? 'async' : '';
            if (!empty($script['url'])) {
                echo '<script type="text/javascript" src="' . $script['url'] . '" ' . $async . '></script>';
            }
        } elseif ($script['tag'] === 'noscript') {
            if (!empty($script['content'])) {
                echo '<noscript>' . $script['content'] . '</noscript>';
            }
        }
    }
}
