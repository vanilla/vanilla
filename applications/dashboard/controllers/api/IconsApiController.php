<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Models\IconModel;
use Vanilla\Models\Model;
use Vanilla\Permissions;

/**
 * List all active icons.
 */
class IconsApiController extends \AbstractApiController
{
    public function __construct(private IconModel $iconModel, private \Gdn_Database $db)
    {
    }

    /**
     * GET /api/v2/icons/active
     *
     * List all active icons.
     *
     * @return array
     */
    public function get_active()
    {
        $this->permission(Permissions::BAN_PRIVATE);

        $icons = $this->iconModel->getAllActiveIcons();

        $this->normalizeForOutput($icons);

        return $icons;
    }

    /**
     * GET /api/v2/icons/system
     *
     * List all system icons.
     *
     * @return array
     */
    public function get_system()
    {
        $this->permission(Permissions::BAN_PRIVATE);
        $icons = $this->iconModel->getAllCoreIcons();

        $this->normalizeForOutput($icons);
        return $icons;
    }

    /**
     * Delete an uploaded custom icon.
     *
     * @param string $id
     * @return void
     * @throws \Exception
     */
    public function delete(string $id): void
    {
        $this->permission("site.manage");

        $icon = $this->iconModel->selectSingle(["iconUUID" => $id]);
        if ($icon["isActive"]) {
            throw new ClientException("Cannot delete an active icon.", 400);
        }

        $this->iconModel->delete(["iconUUID" => $id]);
    }

    /**
     * GET /api/v2/icons/by-name?iconName=:iconName
     * List all active icon variations.
     *
     * @param array $query
     *
     * @throws \Exception
     */
    public function get_byName(array $query)
    {
        $this->permission("site.manage");

        $in = Schema::parse([
            "iconName:s",
            "page:i" => ["default" => 1],
            "limit:i" => [
                "default" => 100,
            ],
        ]);
        $query = $in->validate($query);

        $name = $query["iconName"];

        $coreIcon = $this->iconModel->findCoreIcon($name);
        if ($coreIcon === null) {
            throw new NotFoundException("Icon");
        }

        [$offset, $limit] = ApiUtils::offsetLimit($query);

        $customIcons = $this->iconModel->select(
            [
                "iconName" => $name,
            ],
            options: [
                Model::OPT_LIMIT => $limit,
                Model::OPT_OFFSET => $offset,
                Model::OPT_ORDER => "-dateInserted",
            ]
        );

        // Core icon is active if there are no active custom icons.
        $isCoreIconActive = count(array_filter($customIcons, fn($icon) => $icon["isActive"])) === 0;
        $coreIcon["isActive"] = $isCoreIconActive;

        if ($query["page"] === 1) {
            $customIcons = array_merge([$coreIcon], $customIcons);
        }

        $this->normalizeForOutput($customIcons);

        $count = $this->iconModel->selectPagingCount(["iconName" => $name]) + 1;

        return new Data(
            $customIcons,
            meta: [
                "paging" => ApiUtils::numberedPagerInfo($count, "/api/v2/icons/by-name", $query, $in),
            ]
        );
    }

    /**
     * POST /api/v2/icons/override
     * Override one or more icons.
     *
     * @param array $body
     * @return array
     */
    public function post_override(array $body)
    {
        $this->permission("site.manage");

        $singleIconSchema = Schema::parse(["iconName:s", "svgRaw:s", "svgContents:s", "svgAttributes:o"]);
        $singleIconSchema->addFilter("", function (array $value, ValidationField $field) {
            $svg = $value["svgRaw"];

            try {
                return $this->iconModel->tryExtractIconFromRawSvg($svg) + $value;
            } catch (ClientException $ex) {
                $field->addError($ex->getMessage(), [
                    "status" => 422,
                ]);
                return Invalid::value();
            }
        });

        $in = Schema::parse(["iconOverrides:a" => $singleIconSchema]);
        $body = $in->validate($body);

        $createdIconUUIDs = [];
        $this->db->runWithTransaction(function () use ($body, &$createdIconUUIDs) {
            foreach ($body["iconOverrides"] as $icon) {
                $iconName = $icon["iconName"];

                // De-activate existing overrides.
                $this->iconModel->update(
                    set: ["isActive" => false],
                    where: [
                        "iconName" => $iconName,
                    ]
                );

                // Insert new ones.
                $iconID = $this->iconModel->insert(
                    [
                        "iconName" => $iconName,
                        "isActive" => true,
                    ] + $icon
                );
                $createdIconUUIDs[] = $iconID;
            }
        });

        $createdIcons = $this->iconModel->select(["iconUUID" => $createdIconUUIDs]);

        $this->normalizeForOutput($createdIcons);

        return $createdIcons;
    }

    /**
     * POST /api/v2/icons/restore
     *
     * Restore one or more icons.
     *
     * @param array $body
     *
     * @return array
     */
    public function post_restore(array $body)
    {
        $this->permission("site.manage");

        $iconRestorationSchema = Schema::parse(["iconName:s", "iconUUID:s"]);

        $in = Schema::parse(["restorations:a" => $iconRestorationSchema]);
        $body = $in->validate($body);

        $this->db->runWithTransaction(function () use ($body) {
            foreach ($body["restorations"] as $restoration) {
                $this->iconModel->update(
                    set: ["isActive" => false],
                    where: [
                        "iconName" => $restoration["iconName"],
                    ]
                );

                $this->iconModel->update(
                    set: [
                        "isActive" => true,
                    ],
                    where: [
                        "iconUUID" => $restoration["iconUUID"],
                    ]
                );
            }
        });

        return [];
    }

    /**
     * @param array $iconRows
     */
    public function normalizeForOutput(array &$iconRows): void
    {
        foreach ($iconRows as &$iconRow) {
            if (empty($iconRow["svgAttributes"])) {
                $iconRow["svgAttributes"] = new \stdClass();
            }

            $iconRow["isActive"] = $iconRow["isActive"] ?? false;
            $iconRow["isCustom"] = $iconRow["isCustom"] ?? true;
        }
    }
}
