<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Modules;

use Garden\Container\Container;
use Garden\EventManager;
use Garden\JsonFilterTrait;
use Garden\Schema\ValidationException;
use Vanilla\Web\TwigStaticRenderer;

/**
 * Class for converting legacy category items into new modules.
 */
class FoundationDiscussionsShim
{
    use JsonFilterTrait;

    /** @var \DiscussionsApiController */
    private $discussionsApiController;

    /** @var Container */
    private $container;

    /** @var \UserModel */
    private $userModel;

    /** @var \CategoryModel */
    private $categoryModel;

    /** @var \TagModel */
    private $tagModel;

    /** @var EventManager */
    private $eventManager;

    /**
     * DI.
     *
     * @param \DiscussionsApiController $discussionsApiController
     * @param Container $container
     * @param \UserModel $userModel
     * @param \CategoryModel $categoryModel
     * @param \TagModel $tagModel
     * @param EventManager $eventManager
     */
    public function __construct(
        \DiscussionsApiController $discussionsApiController,
        Container $container,
        \UserModel $userModel,
        \CategoryModel $categoryModel,
        \TagModel $tagModel,
        EventManager $eventManager
    ) {
        $this->discussionsApiController = $discussionsApiController;
        $this->container = $container;
        $this->userModel = $userModel;
        $this->categoryModel = $categoryModel;
        $this->tagModel = $tagModel;
        $this->eventManager = $eventManager;
    }

    /**
     * Render a list of legacy-style discussion data as the new discussion list widget.
     *
     * @param array $legacyDiscussions
     * @param FoundationShimOptions|null $options
     * @return string
     */
    public function renderShim(array $legacyDiscussions, ?FoundationShimOptions $options = null): string
    {
        $newStyleDiscussions = $this->convertLegacyData($legacyDiscussions);

        /** @var DiscussionListModule $module */
        $module = $this->container->get(DiscussionListModule::class);
        $module->setDiscussions($newStyleDiscussions);
        $module->applyOptions($options ?? FoundationShimOptions::create());
        $module->setIsMainContent($options->isMainContent());

        return $module->toString();
    }

    /**
     * Static utility to drop in old rendering places.
     *
     * @param array $legacyDiscussions
     * @param FoundationShimOptions|null $options
     */
    public static function printLegacyShim(array $legacyDiscussions, ?FoundationShimOptions $options = null)
    {
        echo self::renderLegacyShim($legacyDiscussions, $options);
    }

    /**
     * Static utility to drop in old rendering places.
     *
     * @param array $legacyDiscussions
     * @param FoundationShimOptions|null $options
     *
     * @return string
     */
    public static function renderLegacyShim(array $legacyDiscussions, ?FoundationShimOptions $options = null): string
    {
        /** @var FoundationDiscussionsShim $shim */
        $shim = \Gdn::getContainer()->get(FoundationDiscussionsShim::class);
        return $shim->renderShim($legacyDiscussions, $options);
    }

    /**
     * Static utility to see if we should render with the shim or not.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return \Gdn::config("Vanilla.Discussions.Layout") === "foundation";
    }

    /**
     * Utility for for mapping discussion data into the new style.
     *
     * @param array $discussions
     * @return array
     */
    public function convertLegacyData(array $discussions): array
    {
        $expands = ["category", "insertUser", "lastUser", "-body", "excerpt"];
        $normalized = [];
        $schema = $this->discussionsApiController->discussionSchema();
        foreach ($discussions as $discussion) {
            if (empty($discussion)) {
                continue;
            }
            if (is_object($discussion)) {
                // Some parts of the codebase may have been working with objects here.
                $discussion = (array) $discussion;
            }
            $normalizedItem = $this->discussionsApiController->normalizeOutput($discussion, $expands);
            $normalized[] = $normalizedItem;
        }

        // Apply user expands that may not have been in the original data.
        $this->userModel->expandUsers($normalized, ["insertUserID", "lastUserID"]);

        // There is almost the data needed to reconstruct these manually, but it may not be there in all scenarios.
        // In any case the category cache in the app should be primed already from the initial fetch.
        // It's worth the CPU cycles to be sure.
        $this->categoryModel->expandCategories($normalized, "category");

        // Apply validation
        foreach ($normalized as &$normalizedItem) {
            $discussionID = $normalizedItem["discussionID"] ?? null;
            try {
                $normalizedItem = $schema->validate($normalizedItem);
            } catch (ValidationException $e) {
                trigger_error(
                    "Discussion `$discussionID` rendering was skipped due to validation errors.\n" .
                        formatException($e),
                    E_USER_WARNING
                );
            }
        }

        $result = array_values(array_filter($normalized));
        $this->tagModel->expandTags($result);

        // If reactions is enabled expand them on the results.
        $result = $this->eventManager->fireFilter(
            "discussionsApiController_getOutput",
            $result,
            $this->discussionsApiController,
            $schema,
            ["expand" => ["reactions"]],
            $discussions
        );

        return $result;
    }
}
