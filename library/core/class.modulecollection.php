<?php
/**
 * Gdn_ModuleCollection
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Module collection.
 */
class Gdn_ModuleCollection extends Gdn_Module {

    /** @var array  */
    public $Items = array();

    /**
     *
     *
     * @throws Exception
     */
    public function render() {
        $RenderedCount = 0;
        foreach ($this->Items as $Item) {
            $this->EventArguments['AssetName'] = $this->AssetName;

            if (is_string($Item)) {
                if (!empty($Item)) {
                    if ($RenderedCount > 0) {
                        $this->fireEvent('BetweenRenderAsset');
                    }

                    echo $Item;
                    $RenderedCount++;
                }
            } elseif ($Item instanceof Gdn_IModule) {
                if (!GetValue('Visible', $Item, true)) {
                    continue;
                }

                $LengthBefore = ob_get_length();
                $Item->render();
                $LengthAfter = ob_get_length();

                if ($LengthBefore !== false && $LengthAfter > $LengthBefore) {
                    if ($RenderedCount > 0) {
                        $this->fireEvent('BetweenRenderAsset');
                    }
                    $RenderedCount++;
                }
            } else {
                throw new Exception();
            }
        }
        unset($this->EventArguments['AssetName']);
    }

    /**
     * Build output HTML.
     *
     * @return string
     * @throws Exception
     */
    public function toString() {
        ob_start();
        $this->render();
        return ob_get_clean();
    }
}
