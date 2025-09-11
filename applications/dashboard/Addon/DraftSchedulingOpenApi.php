<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Addon;

use Vanilla\Models\ContentDraftModel;
use Vanilla\Utility\ArrayUtils;

class DraftSchedulingOpenApi
{
    public function __invoke(array &$openApi): void
    {
        if (ContentDraftModel::draftSchedulingEnabled()) {
            $this->setIndexRequestResponse($openApi);
            $this->setEditDraftResponses($openApi);
            $this->setPostPatchResponses($openApi);
        }
    }

    /**
     * Add new properties to draft index request
     *
     * @param array $openApi
     * @return void
     */
    private function setIndexRequestResponse(array &$openApi): void
    {
        $newProperties = [];
        $scheduledProperties = [
            ['$ref' => "#/components/parameters/DraftStatus"],
            ['$ref' => "#/components/parameters/DateUpdated"],
            ['$ref' => "#/components/parameters/DateScheduled"],
            ['$ref' => "#/components/parameters/Sort"],
            ['$ref' => "#/components/parameters/Expand"],
        ];
        $currentProperties = ArrayUtils::getByPath("paths./drafts.get.parameters", $openApi, []);
        foreach ($currentProperties as $value) {
            if (!empty($value['$ref']) && $value['$ref'] === "#/components/parameters/Page") {
                $newProperties = array_merge($newProperties, $scheduledProperties);
            }
            $newProperties[] = $value;
        }
        ArrayUtils::setByPath("paths./drafts.get.parameters", $openApi, $newProperties);

        $responseParams = ArrayUtils::getByPath(
            "paths./drafts.get.responses.200.content.application/json.schema.items.properties",
            $openApi,
            []
        );
        $contentDraftProperties = ArrayUtils::getByPath("components.schemas.DraftContent.properties", $openApi, []);
        $scheduledDraftProperties = ArrayUtils::getByPath("components.schemas.DraftSchedule.properties", $openApi, []);
        ArrayUtils::setByPath(
            "paths./drafts.get.responses.200.content.application/json.schema.items.properties",
            $openApi,
            array_merge($responseParams, $contentDraftProperties, $scheduledDraftProperties)
        );
    }

    /**
     * Set edit draft responses
     *
     * @param array $openApi
     * @return void
     */
    private function setEditDraftResponses(array &$openApi): void
    {
        $scheduledDraftProperties = ArrayUtils::getByPath("components.schemas.ScheduleList.properties", $openApi, []);
        $responseParams = ArrayUtils::getByPath(
            "paths./drafts/{id}.get.responses.200.content.application/json.schema.properties",
            $openApi,
            []
        );
        ArrayUtils::setByPath(
            "paths./drafts/{id}.get.responses.200.content.application/json.schema.properties",
            $openApi,
            array_merge($responseParams, $scheduledDraftProperties)
        );

        $postRequired = ArrayUtils::getByPath(
            "paths./drafts/{id}.get.responses.200.content.application/json.schema.required",
            $openApi,
            []
        );
        $postRequired[] = "draftStatus";
        ArrayUtils::setByPath(
            "paths./drafts/{id}.get.responses.200.content.application/json.schema.required",
            $openApi,
            $postRequired
        );

        $getEditDraftResponse = ArrayUtils::getByPath(
            "paths./drafts/{id}/edit.get.responses.200.content.application/json.schema.properties",
            $openApi,
            []
        );
        unset($scheduledDraftProperties["recordID"]);
        ArrayUtils::setByPath(
            "paths./drafts/{id}/edit.get.responses.200.content.application/json.schema.properties",
            $openApi,
            array_merge($getEditDraftResponse, $scheduledDraftProperties)
        );

        $getEditRequired = ArrayUtils::getByPath(
            "paths./drafts/{id}/edit.get.responses.200.content.application/json.schema.required",
            $openApi,
            []
        );
        ArrayUtils::setByPath(
            "paths./drafts/{id}/edit.get.responses.200.content.application/json.schema.required",
            $openApi,
            array_merge($getEditRequired, ["draftStatus", "dateScheduled"])
        );
    }

    /**
     * Add new properties for post / patch responses
     */

    private function setPostPatchResponses(array &$openApi): void
    {
        $scheduledSchemaProperties = [
            '$ref' => "#/components/schemas/DraftPostPatchSchedule",
        ];
        // Update post request body
        ArrayUtils::setByPath(
            "paths./drafts.post.requestBody.content.application/json.schema",
            $openApi,
            $scheduledSchemaProperties
        );
        // Update patch request body
        ArrayUtils::setByPath(
            "paths./drafts/{id}.patch.requestBody.content.application/json.schema",
            $openApi,
            $scheduledSchemaProperties
        );

        $scheduledDraftProperties = ArrayUtils::getByPath("components.schemas.ScheduleList.properties", $openApi, []);

        // update post required fields
        $postRequired = ArrayUtils::getByPath(
            "paths./drafts.post.responses.201.content.application/json.schema.required",
            $openApi,
            []
        );
        $postRequired[] = "draftStatus";
        ArrayUtils::setByPath(
            "paths./drafts.post.responses.201.content.application/json.schema.required",
            $openApi,
            $postRequired
        );

        //update patch required fields
        $patchRequired = ArrayUtils::getByPath(
            "paths./drafts/{id}.patch.responses.200.content.application/json.schema.required",
            $openApi,
            []
        );
        $patchRequired[] = "draftStatus";
        ArrayUtils::setByPath(
            "paths./drafts/{id}.patch.responses.200.content.application/json.schema.required",
            $openApi,
            $patchRequired
        );

        //update post response
        $postResponse = ArrayUtils::getByPath(
            "paths./drafts.post.responses.201.content.application/json.schema.properties",
            $openApi,
            []
        );
        ArrayUtils::setByPath(
            "paths./drafts.post.responses.201.content.application/json.schema.properties",
            $openApi,
            array_merge($postResponse, $scheduledDraftProperties)
        );

        //update patch response

        $patchResponse = ArrayUtils::getByPath(
            "paths./drafts/{id}.patch.responses.200.content.application/json.schema.properties",
            $openApi,
            []
        );
        ArrayUtils::setByPath(
            "paths./drafts/{id}.patch.responses.200.content.application/json.schema.properties",
            $openApi,
            array_merge($patchResponse, $scheduledDraftProperties)
        );
    }
}
