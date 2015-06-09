<?php
/**
 * Slice manager: plugins and controllers
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Allows plugns and controllers to implement small asynchronously refreshable portions of the page - slices.
 */
class Gdn_SliceProvider {

    protected $SliceHandler;

    protected $SliceConfig;

    /**
     *
     *
     * @param $Sender
     */
    public function enableSlicing($Sender) {
        $this->SliceHandler = $Sender;
        $this->SliceConfig = array(
            'css' => array(),
            'js' => array()
        );
        $Sender->addJsFile('/js/library/jquery.class.js');
        $Sender->addJsFile('/js/slice.js');
        $Sender->addCssFile('/applications/dashboard/design/slice.css');
    }

    /**
     *
     *
     * @param $SliceName
     * @param array $Arguments
     * @return Gdn_Slice
     */
    public function slice($SliceName, $Arguments = array()) {
        $CurrentPath = Gdn::request()->path();
        $ExplodedPath = explode('/', $CurrentPath);
        switch ($this instanceof Gdn_IPlugin) {
            case true:
                $ReplacementIndex = 2;
                break;

            case false:
                $ReplacementIndex = 1;
                break;
        }

        if ($ExplodedPath[0] == strtolower(Gdn::dispatcher()->application()) && $ExplodedPath[1] == strtolower(Gdn::dispatcher()->controller())) {
            $ReplacementIndex++;
        }

        $ExplodedPath[$ReplacementIndex] = $SliceName;
        $SlicePath = implode('/', $ExplodedPath);
        return Gdn::Slice($SlicePath);
    }

    /**
     *
     *
     * @param $Asset
     */
    public function addSliceAsset($Asset) {
        $Extension = strtolower(array_pop($Trash = explode('.', basename($Asset))));
        switch ($Extension) {
            case 'css':
                if (!in_array($Asset, $this->SliceConfig['css'])) {
                    $this->SliceConfig['css'][] = $Asset;
                }
                break;

            case 'js':
                if (!in_array($Asset, $this->SliceConfig['js'])) {
                    $this->SliceConfig['js'][] = $Asset;
                }
                break;
        }
    }

    /**
     *
     *
     * @return string
     */
    public function renderSliceConfig() {
        return json_encode($this->SliceConfig);
    }
}
