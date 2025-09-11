<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Container\Container;
use Garden\Schema\Schema;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\PrimaryKeyUuidProcessor;
use Vanilla\Layout\LayoutModel;
use Vanilla\Layout\Resolvers\CustomFragmentResolver;
use Vanilla\Layout\Resolvers\ReactResolver;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Models\Model;
use Vanilla\Models\ModelCache;
use Vanilla\Theme\ThemeService;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Widgets\React\CustomFragmentWidget;
use Webmozart\Assert\Assert;

class FragmentModel extends FullRecordCacheModel
{
    public const STATUS_ACTIVE = "active";
    public const STATUS_DRAFT = "draft";
    public const STATUS_PAST_REVISION = "past-revision";
    public const STATUS_DELETED = "deleted";

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_DRAFT, self::STATUS_PAST_REVISION, self::STATUS_DELETED];

    public function __construct(
        \Gdn_Cache $cache,
        \Gdn_Session $session,
        private LayoutModel $layoutModel,
        private ThemeService $themeService
    ) {
        parent::__construct("fragment", $cache, [
            ModelCache::OPT_TTL => 60 * 60 * 12, // 12 hour cache, will be invalided when fragments are updated.
        ]);
        $this->setPrimaryKey("fragmentRevisionUUID");
        $this->addInsertUpdateProcessors();
        $this->addPipelineProcessor(
            (new CurrentUserFieldProcessor($session))->setInsertFields(["revisionInsertUserID"])->setUpdateFields([])
        );
        $this->addPipelineProcessor(
            new CurrentDateFieldProcessor(insertFields: ["dateRevisionInserted"], updateFields: [])
        );
        $this->addPipelineProcessor(new PrimaryKeyUuidProcessor("fragmentRevisionUUID"));
        $this->addPipelineProcessor($this->modelCache->createInvalidationProcessor());
        $this->addPipelineProcessor(new JsonFieldProcessor(["previewData", "files"], JSON_THROW_ON_ERROR));
        $this->addPipelineProcessor(new JsonFieldProcessor(["customSchema"], JSON_FORCE_OBJECT));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["isLatest"]));
    }

    public static function structure(\Gdn_Database $database)
    {
        $structure = $database->structure();

        $structure
            ->table("fragment")
            ->column("fragmentRevisionUUID", "varchar(40)", keyType: "primary")
            ->column("fragmentUUID", "varchar(40)", keyType: "index")
            ->column("fragmentType", "varchar(100)")
            ->column("status", self::STATUSES, nullDefault: self::STATUS_DRAFT, keyType: "index")
            ->column("isLatest", "tinyint(1)", nullDefault: 0, keyType: "index")
            ->column("name", "varchar(255)")
            ->column("revisionInsertUserID", "int")
            ->column("dateRevisionInserted", "datetime")
            ->column("commitMessage", "text", nullDefault: true)
            ->column("commitDescription", "text", nullDefault: true)
            ->column("js", "mediumtext")
            ->column("jsRaw", "mediumtext")
            ->column("css", "mediumtext")
            ->column("previewData", "mediumtext")
            ->column("files", "mediumtext")
            ->column("customSchema", "mediumtext")
            ->set();
    }

    /**
     * Return react resolvers for custom fragments.
     *
     * @return array<CustomFragmentResolver>
     */
    public function getResolvers(Container $container): array
    {
        $customFragments = $this->select(
            ["fragmentType" => "CustomFragment", "status" => self::STATUS_ACTIVE],
            options: [
                Model::OPT_SELECT => [
                    "fragmentUUID",
                    "fragmentRevisionUUID",
                    "fragmentType",
                    "css",
                    "name",
                    "customSchema",
                ],
            ]
        );

        $results = [];
        foreach ($customFragments as &$fragment) {
            // Add urls for assets.
            $fragment["cssUrl"] = $this->assetUrl("css", $fragment);
            $fragment["jsUrl"] = $this->assetUrl("js", $fragment);

            $results[] = new CustomFragmentResolver($fragment, $container);
        }
        return $results;
    }

    /**
     * Get a list of used fragment types.
     *
     * @return array
     */
    public function getUsedFragmentTypes(): array
    {
        $fragmentTypes = $this->modelCache->getCachedOrHydrate([__METHOD__], function () {
            $result = $this->createSql()
                ->select("fragmentType", "DISTINCT", "fragmentType")
                ->from($this->getTable())
                ->where([
                    "status" => self::STATUS_ACTIVE,
                ])
                ->get()
                ->column("fragmentType");
            return $result;
        });
        return $fragmentTypes;
    }

    /**
     * @return Schema
     */
    public static function previewDataSchema(): Schema
    {
        return Schema::parse(["previewDataUUID:s", "name:s", "description:s?", "data:o"]);
    }

    public static function fragmentSchema(): Schema
    {
        return Schema::parse([
            "fragmentUUID:s",
            "name:s",
            "fragmentType:s",
            "status:s",
            "isLatest:b",
            "fragmentRevisionUUID:s",
            "revisionInsertUserID:i",
            "dateRevisionInserted:s",
            "commitMessage:s",
            "commitDescription:s?",
        ]);
    }

    /**
     * Get an array of all applied fragment UUIDs.
     *
     * @return string[]
     */
    public function getAppliedFragmentUUIDs(): array
    {
        $views = array_merge($this->layoutModel->getFragmentViews(), $this->themeService->getFragmentViews());
        $fragmentUUIDS = array_map(fn(FragmentView $view) => $view->fragmentUUID, $views);
        return array_values(array_unique($fragmentUUIDS));
    }

    /**
     * Given a fragment or array of fragments, join in the fragment views.
     *
     * @param array $fragmentOrFragments
     *
     * @return void
     */
    public function expandFragmentViews(array &$fragmentOrFragments): void
    {
        $fragmentViews = array_merge($this->layoutModel->getFragmentViews(), $this->themeService->getFragmentViews());

        if (ArrayUtils::isAssociative($fragmentOrFragments)) {
            $fragments = [&$fragmentOrFragments];
        } else {
            $fragments = &$fragmentOrFragments;
        }

        foreach ($fragments as &$fragment) {
            $views = array_values(
                array_filter(
                    $fragmentViews,
                    fn(FragmentView $view) => $view->fragmentUUID === $fragment["fragmentUUID"]
                )
            );
            $fragment["fragmentViews"] = $views;
        }
    }

    /**
     * Given an already hydrated layout, hydrate in fragment metadata for fragments applied in the layout.
     *
     * @param array $layout
     * @return void
     */
    public function hydrateFragments(array &$layout): void
    {
        $fragmentUUIDs = [];
        ArrayUtils::walkRecursiveArray($layout, function ($value) use (&$fragmentUUIDs) {
            /**
             * We're looking of the $fragmentUUIDs values set through the schema {@link ReactResolver::getSchema()} applies.
             */
            if ($impls = $value["\$fragmentImpls"] ?? null) {
                $uuids = array_filter(
                    array_column($impls, "fragmentUUID"),
                    fn($val) => !empty($val) && $val !== "system" && $val !== "styleguide"
                );
                $fragmentUUIDs = array_merge($fragmentUUIDs, $uuids);
            }
        });

        // Now fetch metadata about these fragments.
        $fragmentsByUUID = !empty($fragmentUUIDs) ? $this->selectFragmentImpls($fragmentUUIDs) : [];

        ArrayUtils::walkRecursiveArray($layout, function (&$value) use (&$fragmentsByUUID) {
            // Now we're looking for one level up.
            $propFragmentImpls = $value["\$reactProps"]["\$fragmentImpls"] ?? null;

            if (!empty($propFragmentImpls)) {
                foreach ($propFragmentImpls as $fragmentType => $rawFragmentImpl) {
                    $fragmentUUID = $rawFragmentImpl["fragmentUUID"] ?? null;

                    if (in_array(needle: $fragmentUUID, haystack: ["system", "styleguide"])) {
                        // No need to hydrate these, but let them through anyways.
                        $value["\$fragmentImpls"][$fragmentType] = $rawFragmentImpl;
                    }

                    $fragmentImpl = $fragmentsByUUID[$fragmentUUID] ?? null;
                    if (!$fragmentImpl) {
                        continue;
                    }

                    $value["\$fragmentImpls"][$fragmentType] = $fragmentImpl;
                }
                // We've hoisted it up a level.
                unset($value["\$reactProps"]["\$fragmentImpls"]);
            }
        });
    }

    /**
     * Given a list of fragment UUIDs, select the fragment data needed to hydrate the frontend.
     *
     * @param array $fragmentUUIDs
     *
     * @return array<string, array> Map of fragmentUUID => data for {@link self::makeImplData()}
     */
    public function selectFragmentImpls(array $fragmentUUIDs): array
    {
        try {
            $this->getReadSchema();
        } catch (\Throwable $ex) {
            // Expected, we might not have bootstraped the database table yet.
            return [];
        }

        $fragments = $this->select(
            ["fragmentUUID" => $fragmentUUIDs, "status" => self::STATUS_ACTIVE],
            options: [
                Model::OPT_SELECT => [
                    "fragmentUUID",
                    "fragmentRevisionUUID",
                    "css", // We're inlining this for efficiency.
                ],
            ]
        );

        foreach ($fragments as &$fragment) {
            $fragment["cssUrl"] = $this->assetUrl("css", $fragment);
            $fragment["jsUrl"] = $this->assetUrl("js", $fragment);
        }
        $fragmentsByUUID = array_column($fragments, null, "fragmentUUID");
        return $fragmentsByUUID;
    }

    /**
     * @param string $type
     * @param array $fragment
     *
     * @return string
     */
    public function assetUrl(string $type, array $fragment)
    {
        Assert::oneOf($type, ["js", "css"]);
        return url(
            "/api/v2/fragments/{$fragment["fragmentUUID"]}/{$type}?fragmentRevisionUUID={$fragment["fragmentRevisionUUID"]}",
            true
        );
    }
}
