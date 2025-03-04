<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Web\Exception\ClientException;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\PrimaryKeyUuidProcessor;
use Vanilla\FileUtils;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\ModelCache;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\HtmlUtils;
use Vanilla\Web\JsInterpop\PhpAsJsVariable;
use Vanilla\Web\TwigStaticRenderer;

class IconModel extends PipelineModel
{
    const CORE_ICONS_DIR = PATH_ROOT . "/packages/vanilla-icons/icons";

    protected ModelCache $modelCache;

    public function __construct(\Gdn_Cache $cache, private \UserModel $userModel)
    {
        parent::__construct("icon");
        $this->setPrimaryKey("iconUUID");
        $this->addInsertUpdateProcessors();
        $this->modelCache = new ModelCache("icon", $cache);
        $this->addPipelineProcessor(new PrimaryKeyUuidProcessor("iconUUID"));
        $this->addPipelineProcessor($this->modelCache->createInvalidationProcessor());
        $this->addPipelineProcessor(new JsonFieldProcessor(["svgAttributes"]));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["isActive"]));
    }

    public static function structure(\Gdn_Database $database)
    {
        $structure = $database->structure();
        $structure
            ->table("icon")
            ->column("iconUUID", "varchar(40)", keyType: "primary")
            ->column("svgRaw", "text")
            ->column("svgContents", "text")
            ->column("svgAttributes", "json")
            ->column("iconName", "varchar(100)")
            ->column("isActive", "tinyint(1)")
            ->insertUpdateColumns()
            ->set();
    }

    /**
     * @return PhpAsJsVariable
     */
    public function getActiveIconScriptData(): PhpAsJsVariable
    {
        $iconAttrs = [];

        try {
            $icons = $this->getAllActiveIcons();
            foreach ($icons as $icon) {
                $iconAttrs[$icon["iconName"]] = $icon["svgAttributes"];
            }
            return new PhpAsJsVariable([
                "__VANILLA_ICON_ATTRS__" => $iconAttrs,
            ]);
        } catch (\Throwable $e) {
            // Don't let a possible corruption in icons *ever* totally break the site.
            ErrorLogger::critical("Failed to render icon scripts", ["masterView", "icons"], ["exception" => $e]);
            return new PhpAsJsVariable([]);
        }
    }

    /**
     * Get the active icon definitions to be inline in a page.
     *
     * @return string
     */
    public function getActiveIconDefinitions(): string
    {
        try {
            $icons = $this->getAllActiveIcons();
            $definitions = "";

            foreach ($icons as $icon) {
                // The rest of the attributes can't be put on a <symbol>, instead it needs to be rendered on the svg attribute
                // in the react code itself.
                $attributes = [
                    "data-uuid" => $icon["iconUUID"],
                    "data-is-custom" => $icon["isCustom"] ? "true" : "false",
                    "id" => $icon["iconName"],
                ];

                $attributesStr = HtmlUtils::attributes($attributes);

                $definitions .= "<symbol $attributesStr>{$icon["svgContents"]}</symbol>";
            }

            return <<<HTML
<div id="vanilla-icon-defs">
    <svg id="vanilla-icon-defs-preloaded" style="display: none;">
        <defs>
            $definitions
        </defs>
    </svg>
</div>
HTML;
        } catch (\Throwable $e) {
            // Don't let a possible corruption in icons *ever* totally break the site.
            ErrorLogger::critical("Failed to render icon definitions", ["masterView", "icons"], ["exception" => $e]);
            return "";
        }
    }

    /**
     * Get all active icons.
     *
     * @return array
     */
    public function getAllActiveIcons(): array
    {
        $coreIcons = $this->getAllCoreIcons();

        try {
            $dbIcons = $this->modelCache->getCachedOrHydrate(
                ["active"],
                function () {
                    return $this->select(where: ["isActive" => 1]);
                },
                [ModelCache::OPT_TTL => 3600]
            );
        } catch (\Throwable $e) {
            // this is a totally valid case when loading a master view if the icons table hasn't been created yet, such as during site structuring.
            ErrorLogger::warning("Failed to get active icons", ["icons"], ["exception" => $e]);
            $dbIcons = [];
        }

        $result = [];

        $dbIconsByName = array_column($dbIcons, null, "iconName");

        foreach ($coreIcons as $coreIcon) {
            $iconName = $coreIcon["iconName"];
            $customIcon = $dbIconsByName[$iconName] ?? null;

            if ($customIcon !== null) {
                $result[] = $customIcon + [
                    "isCustom" => true,
                    "isActive" => true,
                ];
            } else {
                $result[] = $coreIcon + [
                    "isCustom" => false,
                    "isActive" => true,
                ];
            }
        }

        return $result;
    }

    /**
     * Get all core icons (not custom ones in the database).
     *
     * @return array
     */
    public function getAllCoreIcons(): array
    {
        $icons = FileUtils::getCached(PATH_CACHE . "/icons.php", function () {
            return $this->fetchCoreIcons();
        });

        $systemUserID = $this->userModel->getSystemUserID();
        foreach ($icons as &$icon) {
            $icon["insertUserID"] = $systemUserID;
            $icon["dateInserted"] = "2025-01-01T00:00:00Z";
        }
        return $icons;
    }

    /**
     * Find a core icon by it's icon name.
     *
     * @param string $iconName
     *
     * @return array|null
     */
    public function findCoreIcon(string $iconName): ?array
    {
        $icons = $this->getAllCoreIcons();
        $icon = array_find($icons, fn($icon) => $icon["iconName"] === $iconName);
        return $icon;
    }

    /**
     * Fetch all core icons.
     *
     * Internal/uncached version of {@link self::getAllCoreIcons()}
     *
     * @return array
     */
    public function fetchCoreIcons(): array
    {
        $iconFilePaths = glob(self::CORE_ICONS_DIR . "/*.svg");
        $icons = [];
        foreach ($iconFilePaths as $iconFilePath) {
            $icon = file_get_contents($iconFilePath);
            $iconName = pathinfo($iconFilePath, PATHINFO_FILENAME);

            try {
                $icon = $this->tryExtractIconFromRawSvg($icon, $iconFilePath);
                $icons[] =
                    [
                        "iconUUID" => $iconName,
                        "isCustom" => false,
                        "iconName" => $iconName,
                    ] + $icon;
            } catch (ClientException $ex) {
                trigger_error("Invalid SVG file: $iconFilePath", E_USER_WARNING);
                continue;
            }
        }
        return $icons;
    }

    /**
     * Given an svg string, try to extract the icon data from from it and validate it.
     *
     * @param string $svg
     * @param string|null $iconPath
     *
     * @return array
     *
     * @throws ClientException If the SVG is invalid.
     */
    public function tryExtractIconFromRawSvg(string $svg, ?string $iconPath = null): array
    {
        // Step 1: replace colors
        $svgRaw = $svg;
        $svg = str_replace(["#000000", "#555A62"], "currentColor", $svg);

        $iconDom = new HtmlDocument($svg);
        $svgElement = $iconDom->queryCssSelector("svg")->item(0);
        $svgName = $iconPath ? "Core SVG Icon file {$iconPath}" : "Uploaded SVG";

        if (!($svgElement instanceof \DOMElement)) {
            throw new ClientException("{$svgName} is not a valid SVG.", 422, []);
        }

        $attrs = [];
        /**
         * @var \DOMAttr $attr
         */
        foreach ($svgElement->attributes as $attr) {
            $attrs[$attr->nodeName] = $attr->nodeValue;
        }
        $viewBox = $attrs["viewbox"] ?? ($attrs["viewBox"] ?? null);
        if (empty($viewBox)) {
            throw new ClientException("{$svgName} is missing viewBox attribute", 422, []);
        }
        // Make sure viewbox is cased correctly
        $attrs["viewBox"] = $viewBox;
        unset($attrs["viewbox"]);

        if ($strokeWidth = $attrs["strokewidth"] ?? null) {
            $attrs["strokeWidth"] = $strokeWidth;
            unset($attrs["strokewidth"]);
        }

        // Some attributes are getting stripped off
        unset($attrs["id"]);

        // The style attribute in particular needs to be exploded out.
        if (isset($attrs["style"])) {
            $style = $attrs["style"];
            unset($attrs["style"]);
            $styleParts = explode(";", $style);
            $styleAttrs = [];
            foreach ($styleParts as $stylePart) {
                [$key, $value] = explode(":", $stylePart);
                $styleAttrs[trim($key)] = trim($value);
            }
            $attrs["style"] = $styleAttrs;
        }

        $iconContents = $iconDom->elementInnerHtml($svgElement);

        return [
            "svgRaw" => $svgRaw,
            "svgContents" => $iconContents,
            "svgAttributes" => $attrs,
        ];
    }
}
