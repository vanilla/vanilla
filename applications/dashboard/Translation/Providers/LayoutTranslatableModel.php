<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace MachineTranslation\Providers;

use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Layout\LayoutModel;

/**
 * Class to provide custom layout data for translation.
 */
class LayoutTranslatableModel implements TranslatableModelInterface
{
    const CONTENT_TYPE = "layout";

    /**
     * layoutTranslatableModel constructor.
     *
     * @param LayoutModel $layoutModel
     */
    public function __construct(protected LayoutModel $layoutModel)
    {
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string
    {
        return self::CONTENT_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getContentToTranslate(int $primaryID = null, array $data = null): array
    {
        try {
            $layout = $this->layoutModel->getOriginalByID($primaryID);
        } catch (NoResultsException $e) {
            return [];
        }

        return $this->getTranslatableLayoutComponents($layout);
    }

    /**
     * Recursively go through the schema and find all translatable fields.
     *
     * This has the potential to be quite inefficient, but the custom layout UI will likely break before this becomes a problem.
     *
     * @param array $layoutComponents
     * @param string $location
     * @param int $level
     * @param array $completedLocation
     * @return array
     */
    private function getTranslatableLayoutComponents(
        array $layoutComponents,
        string $location = "",
        int $level = 0,
        array &$completedLocation = []
    ): array {
        $componentsToTranslate = [];
        $location = ltrim($location, ".");

        if (in_array($location, $completedLocation)) {
            // We've already gone through this layout component.
            return $completedLocation;
        }

        $titleType = $layoutComponents["titleType"] ?? null;
        $title = $layoutComponents["title"] ?? null;

        if ($titleType == "static" && !empty($title)) {
            $componentsToTranslate[ltrim($location . ".title", ".")] = $title;
        }

        $descriptionType = $layoutComponents["descriptionType"] ?? null;
        $description = $layoutComponents["description"] ?? null;

        if ($descriptionType == "static" && !empty($description)) {
            $componentsToTranslate[ltrim($location . ".description", ".")] = $description;
        }

        // Loop through the components for any more fields to be translated.
        foreach ($layoutComponents as $key => $subComponents) {
            if (is_array($subComponents)) {
                $componentsToTranslate = array_merge(
                    $componentsToTranslate,
                    $this->getTranslatableLayoutComponents(
                        $subComponents,
                        "$location.$key",
                        $level++,
                        $completedLocation
                    )
                );
            }
        }

        $completedLocation[] = $location;
        return $componentsToTranslate;
    }

    /**
     * @inheritdoc
     */
    public function getContentKeysToTranslate(): array
    {
        return ["layout", "Layout"];
    }

    /**
     * @inheritdoc
     */
    public function getPrimaryKey(): string
    {
        return "layoutID";
    }

    public function getObjectKey(array $data): string
    {
        return "";
    }
}
