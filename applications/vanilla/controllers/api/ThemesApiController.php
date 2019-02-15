<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\AddonManager;
use Vanilla\Addon;

/**
 * API Controller for the `/drafts` resource.
 */
class ThemesApiController extends AbstractApiController {

    /* @var AddonManager */
    private $addonManager;

    public function __construct(AddonManager $addonManager) {
        $this->addonManager = $addonManager;
    }

    /**
     * Get a theme assets.
     *
     * @param string $path The unique theme key or theme ID.
     * @return array
     */
    public function get(string $path) {
        $path = ltrim($path,'/');
        if (ctype_digit($path)) {
            return $this->getThemeByID((int)$path);
        }
        $parts = explode('/', $path);
        if (count($parts) === 1) {
            $theme = $this->getThemeByName($path);
            return $this->getThemeAssets($theme);

        }

        var_dump($path);
        die(__FILE__.':'.__FUNCTION__.':'.__LINE__);
    }

    public function getThemeByID(int $themeID) {
        var_dump($themeID);
        die(__FILE__.':'.__FUNCTION__.':'.__LINE__);
    }

    public function get_asset(int $id, string $path) {
        echo $id."\n";
        echo $path."\n";
        die(__FILE__.':'.__FUNCTION__.':'.__LINE__);
    }

    public function getThemeByName(string $themeName) {
        $theme = $this->addonManager->lookupTheme($themeName);
        if (null === $theme) {
            throw new NotFoundException('There is no theme: \''.$themeName.'\' installed.');
        }
        return $theme;
    }

    public function getThemeAssets(Addon $theme) {
       // die(print_r($theme));
        $assets  = $theme->getInfoValue('assets');
        $res = [];
        $res['type'] = 'themeFile';
        $res['themeID'] = $theme->getInfoValue('key');
        $res['ver'] = $theme->getInfoValue('version');
        $res['logos'] = $theme->getInfoValue('logos');
        $res['mobileLogo'] = $theme->getInfoValue('mobileLogo');
        foreach ($assets as $aKey => &$aVal) {
            $aVal['filename'] = PATH_ROOT.$theme->getSubdir().'/assets/'.$aVal['file'];
            $aVal['data'] = file_get_contents($aVal['filename']);
        }
        $res['assets'] = $assets;
        return json_encode($res);
    }

}
