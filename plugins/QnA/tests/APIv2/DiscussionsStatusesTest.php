<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\QnA\APIv2;

use Vanilla\AddonManager;
use Vanilla\Models\AddonModel;
use VanillaTests\APIv2\AbstractResourceTest;
use VanillaTests\APIv2\NoGetEditTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test the /api/v2/discussions/statuses endpoints.
 */
class DiscussionsStatusesTest extends AbstractResourceTest
{
    use NoGetEditTestTrait, CommunityApiTestTrait;

    /** @var string[] */
    public static $addons = ["qna"];

    /** @var string */
    protected $baseUrl = "/discussions/statuses";

    /** @var string[] */
    protected $patchFields = ["isDefault", "name", "state", "recordSubtype"];

    /** @var string */
    protected $pk = "statusID";

    /** @var array */
    protected $record = [
        "isDefault" => false,
        "name" => "foo",
        "state" => "open",
        "recordSubtype" => "test",
    ];

    /** @var bool */
    protected $testPagingOnIndex = false;

    /**
     * @inheritDoc
     */
    public function record()
    {
        static $inc = 1;
        $record = $this->record;
        $record["name"] .= " " . $inc++;
        return $record;
    }

    /**
     * Test the `/discussions/statuses` API endpoint for Questions' statuses when the QnA plugin is enabled/disabled.
     */
    public function testIdeationRecordStatusesOnPluginStatus()
    {
        // We should have questions' statuses by default when the plugin is enabled.
        $this->assertTrue($this->hasQuestionStatuses());

        // Disable the plugin
        $this->enableThisPlugin(false);

        // We shouldn't have questions' statuses once the plugin is disabled.
        $this->assertFalse($this->hasQuestionStatuses());

        // Re-enable the plugin
        $this->enableThisPlugin();

        // We should have questions' statuses back when the plugin is re-enabled.
        $this->assertTrue($this->hasQuestionStatuses());
    }

    /**
     * Enable/Disable the QnA plugin.
     *
     * @param $enable
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    private function enableThisPlugin($enable = true)
    {
        $addonManager = self::container()->get(AddonManager::class);
        $addonModel = self::container()->get(AddonModel::class);

        $function = $enable ? "enable" : "disable";

        $addonModel->$function($addonManager->lookupAddon("QnA"));
    }

    /**
     * Verify that the `/discussions/statuses` API endpoint returns records for the Questions' discussion statuses.
     *
     * @return bool
     */
    private function hasQuestionStatuses()
    {
        // Fetch available recordStatuses
        $recordStatuses = $this->api()
            ->get($this->baseUrl, ["subType" => "question"])
            ->getBody();

        // Are question statuses available?
        $ideationStatuses = false;
        foreach ($recordStatuses as $recordStatus) {
            if ($recordStatus["recordSubtype"] == "question") {
                $ideationStatuses = true;
            }
        }

        return $ideationStatuses;
    }
}
