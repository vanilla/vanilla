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

        if (count($parts) === 3) {
            // parts[0] = :themeKey
            // parts[1] = assets
            // parts[2] = :assetKey | :assetFilename
            if ('assets' === $parts[1]) {
                $pathInfo =  pathinfo($parts[2]);
                $theme = $this->getThemeByName($parts[0]);
                $asset =  $this->getThemeAsset($theme, $pathInfo['filename'], !empty($pathInfo['extension']));
                if (empty($pathInfo['extension'])) {
                    // return asset as an array
                    return $asset;
                } else {
                    // return asset as a file
                    return new Data($asset['data'], ['CONTENT_TYPE' => $asset['mime-type']]);
                }
            }
        }
        throw new NotFoundException('Controller not found for: \''.$path.'\'');
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
            $fileName = PATH_ROOT.$theme->getSubdir().'/assets/'.$aVal['file'];
            $aVal['data'] = file_get_contents($fileName);
        }
        $res['assets'] = $assets;
        return json_encode($res);
    }

    public function getThemeAsset(Addon $theme, string $assetKey, bool $mimeType = false) {
        $assets  = $theme->getInfoValue('assets');
        if (key_exists($assetKey, $assets)) {
            $asset = $assets[$assetKey];
            $fileName = PATH_ROOT.$theme->getSubdir().'/assets/'.$asset['file'];
            $asset['data'] = file_get_contents($fileName);
            if ($mimeType) {
                switch ($asset['type']) {
                    case 'json':
                        $asset['mime-type'] = 'application/json';
                        break;
                    case 'js':
                        $asset['mime-type'] = 'application/javascript';
                        break;
                    case 'css':
                        $asset['mime-type'] = 'text/css';
                        break;
                    case 'html':
                    default:
                        $asset['mime-type'] = 'text/html';
                }
            }
        } else {
            throw new NotFoundException('Asset "'.$assetKey.'" not found for "'.$theme->getInfoValue('key').'"');
        }
        return $asset;
    }
}
