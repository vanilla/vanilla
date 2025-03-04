<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Like {@link DraftsTest} but runs with the new community drafts feature flag enabled.
 */
class NewDraftsTest extends DraftsTest
{
    protected bool $useFeatureFlag = true;

    /**
     * @return void
     */
    public function testStoreArbitraryDraftData(): void
    {
        $expectedShape = [
            "recordType" => "my-type",
            "attributes.anything" => "I want",
            "attributes.expecting" => "to get it back",
        ];
        $draft = $this->api()
            ->post("/drafts", [
                "recordType" => "my-type",
                "attributes" => [
                    "anything" => "I want",
                    "expecting" => "to get it back",
                ],
            ])
            ->assertSuccess()
            ->assertJsonObject()
            ->assertJsonObjectLike($expectedShape);

        $this->api()
            ->get("/drafts/{$draft["draftID"]}")
            ->assertSuccess()
            ->assertJsonObjectLike($expectedShape);
    }
}
