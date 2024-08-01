<?php
/**
 * Gdn_ModuleCollection
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

/**
 * Module collection.
 */
class Gdn_ModuleCollection extends Gdn_Module
{
    /** @var array  */
    public $Items = [];

    /**
     *
     *
     * @throws Exception
     */
    public function render()
    {
        $renderedCount = 0;
        foreach ($this->Items as $item) {
            $this->EventArguments["AssetName"] = $this->AssetName;

            if (is_string($item)) {
                if (!empty($item)) {
                    if ($renderedCount > 0) {
                        $this->fireEvent("BetweenRenderAsset");
                    }

                    echo $item;
                    $renderedCount++;
                }
            } elseif ($item instanceof Gdn_IModule) {
                if (!getValue("Visible", $item, true)) {
                    continue;
                }

                $lengthBefore = ob_get_length();
                try {
                    $item->render();
                } catch (\Throwable $ex) {
                    echo \Vanilla\Utility\DebugUtils::renderException($ex, "Error rendering " . get_class($item));
                }
                $lengthAfter = ob_get_length();

                if ($lengthBefore !== false && $lengthAfter > $lengthBefore) {
                    if ($renderedCount > 0) {
                        $this->fireEvent("BetweenRenderAsset");
                    }
                    $renderedCount++;
                }
            } else {
                throw new Exception();
            }
        }
        unset($this->EventArguments["AssetName"]);
    }

    /**
     * Build output HTML.
     *
     * @return string
     * @throws Exception
     */
    public function toString()
    {
        ob_start();
        try {
            $this->render();
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return $output;
    }
}
