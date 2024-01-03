<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Garden\Container\ContainerException;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Controllers\API\LayoutsApiController;
use Vanilla\Exception\PermissionException;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Web\Asset\ExternalAsset;
use Vanilla\Web\JsInterpop\RawReduxAction;
use Vanilla\Web\ThemedPage;

/**
 * Base page for rendering custom layouts.
 */
class LayoutPage extends ThemedPage
{
    /** @var LayoutsApiController */
    public LayoutsApiController $layoutsApiController;

    private InternalClient $internalClient;

    /**
     * @param LayoutsApiController $layoutsApiController
     * @param InternalClient $internalClient
     */
    public function __construct(LayoutsApiController $layoutsApiController, InternalClient $internalClient)
    {
        $this->layoutsApiController = $layoutsApiController;
        $this->internalClient = $internalClient;
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
     * @param LayoutQuery $layoutFormAsset Contains all the parameters for hydration.
     *
     * @return $this
     * @throws ClientException Client Exception.
     * @throws HttpException Http Exception.
     * @throws NotFoundException Not Found Exception.
     * @throws ValidationException Validation Exception.
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws PermissionException
     */
    public function preloadLayout(LayoutQuery $layoutFormAsset): self
    {
        $query = (array) $layoutFormAsset;

        $layoutData = $this->internalClient
            ->get("/layouts/lookup-hydrate", $query + ["includeNoScript" => true])
            ->getBody();
        $this->setSeoContent($layoutData["seo"]["htmlContents"] ?? "");

        $layoutID = $layoutData["layoutID"];
        $layoutWidgetAssets = $this->internalClient->get("/layouts/{$layoutID}/hydrate-assets")->getBody();

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

        if (isset($seo["url"])) {
            $this->setCanonicalUrl($seo["url"]);
        }

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
