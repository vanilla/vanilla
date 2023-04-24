<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Controllers\API\LayoutsApiController;
use Vanilla\Layout\Asset\LayoutFormAsset;
use Vanilla\Web\Asset\ExternalAsset;
use Vanilla\Web\JsInterpop\RawReduxAction;
use Vanilla\Web\ThemedPage;

/**
 * Base page for rendering custom layouts.
 */
class LayoutPage extends ThemedPage
{
    /** @var LayoutsApiController */
    public $layoutsApiController;

    /**
     * Constructor.
     *
     * @throws \Garden\Container\ContainerException Container Axception.
     * @throws \Garden\Container\NotFoundException Not found Exception.
     */
    public function __construct(LayoutsApiController $layoutsApiController)
    {
        $this->layoutsApiController = $layoutsApiController;
    }

    /**
     * In the future this will be responsible for pre-hydrating layout specs.
     *
     * @inheritdoc
     */
    public function initialize()
    {
        // Do nothing.
    }

    /**
     * Get Asset Section.
     *
     * @return string
     */
    public function getAssetSection(): string
    {
        return "layouts";
    }

    /**
     * Preload layout.
     *
     * @param LayoutFormAsset $layoutFormAsset Contains all the parameters for hydration.
     *
     * @return $this
     * @throws ClientException Client Exception.
     * @throws NotFoundException Not Found Exception.
     * @throws \Garden\Schema\ValidationException Validation Exception.
     * @throws \Garden\Web\Exception\HttpException Http Exception.
     */
    public function preloadLayout(LayoutFormAsset $layoutFormAsset): self
    {
        $query = (array) $layoutFormAsset;

        $layoutData = $this->layoutsApiController->get_lookupHydrate($query)->getData();
        $layoutID = $layoutData["layoutID"];
        $layoutWidgetAssets = $this->layoutsApiController->get_hydrateAssets($layoutID)->getData();

        // Our layout needs all of these assets
        foreach ($layoutWidgetAssets["js"] as $scriptPath) {
            $this->addScript(new ExternalAsset($scriptPath));
        }

        foreach ($layoutWidgetAssets["css"] as $cssPath) {
            $this->addLinkTag([
                "rel" => "stylesheet",
                "type" => "text/css",
                "href" => $cssPath,
            ]);
        }

        $seo = $layoutData["seo"];
        $json = json_decode($seo["json-ld"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_OBJECT_AS_ARRAY);
        $this->setJsonLDItems($json);
        $this->setSeoTitle($seo["title"]);
        $this->setSeoDescription($seo["description"]);
        foreach ($seo["meta"] as $meta) {
            $this->addMetaTag($meta);
        }
        foreach ($seo["links"] as $link) {
            $this->addLinkTag($link);
        }

        $reduxActionPending = new RawReduxAction([
            "type" => "@@layouts/lookup/pending",
            "meta" => [
                "arg" => $layoutFormAsset->getArgs(),
            ],
        ]);

        $reduxAction = new RawReduxAction([
            "type" => "@@layouts/lookup/fulfilled",
            "payload" => $layoutData,
            "meta" => [
                "arg" => $layoutFormAsset->getArgs(),
            ],
        ]);
        $this->addReduxAction($reduxActionPending);
        $this->addReduxAction($reduxAction);

        return $this;
    }
}
