<?php

namespace App\Modules\Icon\Services;

use App\Enums\IconLibraryEnum;
use Cache;
use Storage;
use Log;

class IconService
{
    /**
     * Paths where fonts are stored.
     */
    private array $paths = [
        IconLibraryEnum::FA5 => 'data/fonts/fontawesome5.json',
        IconLibraryEnum::MDI => 'data/fonts/materialdesignicons.json',
    ];

    /**
     * Day in number of seconds.
     */
    private const DAY_IN_SECONDS = 60 * 60 * 24;

    /**
     * Cache icons.
     */
    private bool $cache = true;

    /**
     * Get svg icon from icon library.
     *
     * @param string $icon
     * @return null|array
     */
    public function getSvgIcon(string $icon): ?array
    {
        if (empty($icon)) {
            return null;
        }

        $iconArr = $this->parseIcon($icon);

        if (empty($iconArr)) {
            return null;
        }

        return $this->getLibrarySvgIcon($iconArr['library'], $icon);
    }

    /**
     * Get array of svg icons for the given library.
     *
     * @param string $library
     * @param string $variation
     * @return array
     */
    public function getSvgIcons(string $library, string $variation = ''): array
    {
        if (!in_array($library, IconLibraryEnum::getConstants(), true)) {
            return [];
        }

        $suffix = '_svg_icons';

        if (Cache::has($library . $suffix) && $this->cache) {
            $svgIcons = Cache::get($library . $suffix);

            return isset($svgIcons[$variation]) ? $svgIcons[$variation] : $svgIcons;
        }

        if (!Storage::exists($this->paths[$library])) {
            return [];
        }

        $svgIcons = json_decode(Storage::get($this->paths[$library]), true);

        if (!is_array($svgIcons)) {
            return [];
        }

        if ($this->cache) {
            Cache::put($library . $suffix, $svgIcons, self::DAY_IN_SECONDS);
        }

        return isset($svgIcons[$variation]) ? $svgIcons[$variation] : $svgIcons;
    }

    /**
     * Parse icon string to array.
     *
     * @param string $icon
     * @return array|null
     */
    public function parseIcon(string $icon): ?array
    {
        $prefixes = [
            'mdi' => [
                'material-icons ',
                'material-icons-outlined ',
                'material-icons-round ',
                'material-icons-sharp ',
                'material-icons-two-tone ',
            ],
            'fa5' => [
                'fas ',
                'fab ',
                'far ',
            ],
        ];

        if (preg_match('/^(' . trim(implode('|', $prefixes['mdi'])) . ')/', $icon)) {
            return [
                'library' => IconLibraryEnum::MDI,
            ];
        }

        if (preg_match('/^(' . trim(implode('|', $prefixes['fa5'])) . ')/', $icon)) {
            return [
                'library' => IconLibraryEnum::FA5,
            ];
        }

        return null;
    }

    /**
     * Get svg icon for the given library.
     *
     * @param string $library
     * @param string $icon
     * @return null|array
     */
    private function getLibrarySvgIcon(string $library, string $icon): ?array
    {
        $svgIcons = $this->getSvgIcons($library);

        if (empty($svgIcons)) {
            return null;
        }

        switch ($library) {
            case IconLibraryEnum::FA5:
                return self::getFontAwesome5SvgIcon($svgIcons, $icon);
            case IconLibraryEnum::MDI:
                return self::getMDISvgIcon($svgIcons, $icon);
            default:
                return null;
        }
    }

    /**
     * Get font awesome 5 svg icon.
     *
     * @param array $svgIcons
     * @param string $icon
     * @return null|array
     */
    private static function getFontAwesome5SvgIcon(array $svgIcons, string $icon): ?array
    {
        if (empty($icon) || empty($svgIcons)) {
            return null;
        }

        $icon = explode(' ', $icon);

        if (count($icon) <= 1) {
            return null;
        }

        $icon[1] = str_replace('fa-', '', $icon[1]);

        if (empty($svgIcons[$icon[0]]) || empty($svgIcons[$icon[0]][$icon[1]])) {
            return null;
        }

        return $svgIcons[$icon[0]][$icon[1]];
    }

     /**
     * Get material design svg icon.
     *
     * @param array $svgIcons
     * @param string $icon
     * @return null|array
     */
    private static function getMDISvgIcon(array $svgIcons, string $icon): ?array
    {
        if (empty($icon) || empty($svgIcons)) {
            return null;
        }

        $icon = explode(' ', $icon);

        if (count($icon) <= 1) {
            return null;
        }

        $icon[0] = str_replace('material-icons-', '', $icon[0]);
        $icon[0] = str_replace('material-icons', '', $icon[0]);

        if (empty(trim($icon[0]))) {
            $icon[0] = 'baseline';
        }

        if (empty($svgIcons[$icon[0]]) || empty($svgIcons[$icon[0]][$icon[1]])) {
            return null;
        }

        return $svgIcons[$icon[0]][$icon[1]];
    }
}
