<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/discussions/statuses endpoints.
 */
class DiscussionsStatusesTest extends AbstractResourceTest {

    use NoGetEditTestTrait;

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
    public function record() {
        static $inc = 1;
        $record = $this->record;
        $record["name"] .= " " . $inc++;
        return $record;
    }

    /**
     * Test toggling the isDefault flag for a discussion status.
     */
    public function testToggleIsDefault(): void {
        // Create a couple statuses. Ensure one of them is set to be the default.
        $primary = $this->api()->post($this->baseUrl, [
            "name" => __FUNCTION__ . " A",
            "isDefault" => true,
        ])->getBody();
        $this->assertSame(true, $primary["isDefault"]);

        $secondary = $this->api()->post($this->baseUrl, ["name" => __FUNCTION__ . " B"])->getBody();
        $this->assertSame(false, $secondary["isDefault"]);

        // Switch the other status to be the new default.
        $this->api()->patch(
            $this->baseUrl . "/" . $secondary["statusID"],
            ["isDefault" => true]
        );

        // The original default status should no longer have a truthy isDefault flag. The new default status should.
        $primaryUpdated = $this->api()->get($this->baseUrl . "/" . $primary["statusID"])->getBody();
        $this->assertSame(false, $primaryUpdated["isDefault"]);

        $secondaryUpdated = $this->api()->get($this->baseUrl . "/" . $secondary["statusID"])->getBody();
        $this->assertSame(true, $secondaryUpdated["isDefault"]);
    }
}
