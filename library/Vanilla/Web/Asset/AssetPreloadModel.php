<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Asset;

use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\Web\TwigRenderTrait;

/**
 * Model for manager which assets to preload in the page.
 */
class AssetPreloadModel {

    use TwigRenderTrait;

    /** @var AssetInterface[] */
    private $fullScripts = [];

    /** @var AssetInterface[] */
    private $fullStylesheets = [];

    /** @var AssetPreloader[] */
    private $preloads = [];

    /** @var string[] */
    private $uniqueKeys = [];

    /**
     * Add a script preload asset.
     *
     * @param AssetInterface $asset The asset to add.
     * @param string $rel
     * The preload type. REL_FULL is custom and will just add the script like a normal script.
     * The other REL constants on AssetPreloader are standard rel attribute values for a <link /> element.
     * @param string $uniqueKey An optional string key to prevent adding the same asset twice.
     */
    public function addScript(AssetInterface $asset, string $rel = AssetPreloader::REL_PRELOAD, string $uniqueKey = null) {
        if ($uniqueKey !== null) {
            if (in_array($uniqueKey, $this->uniqueKeys)) {
                // The script has already been added.
                return;
            } else {
                $this->uniqueKeys[] = $uniqueKey;
            }
        }

        if ($rel === AssetPreloader::REL_FULL) {
            $this->fullScripts[] = $asset;
        } else {
            $this->preloads[] = new AssetPreloader($asset, $rel, AssetPreloader::AS_SCRIPT);
        }
    }

    /**
     * Add a script preload asset.
     *
     * @param AssetInterface $asset The asset to add.
     * @param string $rel
     * The preload type. REL_FULL is custom and will just add the script like a normal script.
     * The other REL constants on AssetPreloader are standard rel attribute values for a <link /> element.
     * @param string $uniqueKey An optional string key to prevent adding the same asset twice.
     */
    public function addStylesheet(AssetInterface $asset, string $rel = AssetPreloader::REL_PRELOAD, string $uniqueKey = null) {
        if ($uniqueKey !== null) {
            if (in_array($uniqueKey, $this->uniqueKeys)) {
                // The script has already been added.
                return;
            } else {
                $this->uniqueKeys[] = $uniqueKey;
            }
        }

        if ($rel === AssetPreloader::REL_FULL) {
            $this->fullStylesheets[] = $asset;
        } else {
            $this->preloads[] = new AssetPreloader($asset, $rel, AssetPreloader::AS_STYLE);
        }
    }

    /**
     * Render HTMLf or the head element using all of the registered assets in the model.
     */
    public function renderHtml(): string {
        $viewPath = dirname(__FILE__) . '/AssetPreloadModel.twig';
        $html = $this->renderTwig($viewPath, [
            'model' => $this,
        ]);
        return $html;
    }

    /**
     * @return AssetInterface[]
     */
    public function getFullScripts(): array {
        return $this->fullScripts;
    }

    /**
     * @return AssetInterface[]
     */
    public function getFullStylesheets(): array {
        return $this->fullStylesheets;
    }

    /**
     * @return AssetPreloader[]
     */
    public function getPreloads(): array {
        return $this->preloads;
    }
}
