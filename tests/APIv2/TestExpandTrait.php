<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use PHPUnit\Framework\TestCase;
use Vanilla\Http\InternalClient;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

/**
 * Trait for asserting that certain common properties are expanded.
 */
trait TestExpandTrait {

    /**
     * Test the expand parameters on the groups API.
     *
     * @group expands
     */
    public function testExpandIndex() {
        $notExpanded = $this->testIndex();
        $this->assertUsersNotExpanded($notExpanded);
        $this->assertCrawlNotExpanded($notExpanded);

        // Fetch with expands
        $expands = array_merge([ModelUtils::EXPAND_CRAWL], $this->getExpandableUserFields());
        $expanded = $this->api()->get($this->indexUrl(), ['expand' => $expands])->getBody();

        $this->assertUsersExpanded($expanded);
        $this->assertCrawlExpanded($expanded);

        // Test private community crawl.
        $this->runWithPrivateCommunity(function () {
            $this->api()->setUserID(InternalClient::DEFAULT_USER_ID);
            $rows = $this->api()->get($this->indexUrl(), ['expand' => ModelUtils::EXPAND_CRAWL]);
            foreach ($rows as $row) {
                TestCase::assertEquals(CrawlableRecordSchema::SCOPE_RESTRICTED, $row['scope']);
            }
        });
    }

    /**
     * Test that expands work on the get.
     *
     * @group expands
     */
    public function testExpandGet() {
        $record = $this->testPost();
        $notExpanded = $this->api()->get($this->baseUrl . '/' . $record[$this->pk])->getBody();
        $this->assertUsersNotExpanded([$notExpanded]);
        $this->assertCrawlNotExpanded([$notExpanded]);

        $expands = array_merge([ModelUtils::EXPAND_CRAWL], $this->getExpandableUserFields());
        $expanded = $this->api()->get($this->baseUrl . '/' . $record[$this->pk], ['expand' => $expands])->getBody();
        $this->assertUsersExpanded([$expanded]);
        $this->assertCrawlExpanded([$expanded]);
    }

    /**
     * Get the expandable user fields. If empty no user expansion will be tested.
     *
     * @return string[]
     */
    abstract protected function getExpandableUserFields();

    /**
     * Assert that some user fields are expanded.
     *
     * @param array $rows
     * @param string[] $userFields
     */
    private function assertUsersExpanded(array $rows, array $userFields = null) {
        $userFields = $userFields ?? $this->getExpandableUserFields();

        $fragmentSchema = new UserFragmentSchema();
        foreach ($rows as $row) {
            foreach ($userFields as $userField) {
                $fragment = ArrayUtils::getByPath($userField, $row, null);
                $this->assertNotNull($fragment, "Failed to find user fragment for field $userField.");
                $fragmentSchema->validate($fragment);
            }
        }
    }

    /**
     * Assert that users have not been expanded.
     *
     * @param array $rows
     * @param array $userFields
     */
    private function assertUsersNotExpanded(array $rows, array $userFields = null) {
        $userFields = $userFields ?? $this->getExpandableUserFields();

        foreach ($rows as $row) {
            foreach ($userFields as $userField) {
                $this->assertArrayNotHasKey('userField', $row);
            }
        }
    }

    /**
     * Assert that rows have fields expected of a crawl expansion.
     *
     * @param array $rows
     */
    private function assertCrawlExpanded(array $rows) {
        foreach ($rows as $row) {
            $scope = $row['scope'] ?? null;
            $this->assertNotNull($scope, "Row did not define any scope.");

            $excerpt = $row['excerpt'] ?? null;
            $this->assertNotNull($excerpt, "Row did not define any excerpt.");

            $this->assertLocaleExpanded($row);
        }
    }

    /**
     * Assert that crawl fields have not been added to the record.
     *
     * @param array $rows
     */
    private function assertCrawlNotExpanded(array $rows) {
        foreach ($rows as $row) {
            $this->assertArrayNotHasKey('scope', $rows);
        }
    }

    /**
     * Assert that locale subfields are added to the record.
     *
     * @param array $row
     */
    private function assertLocaleExpanded(array $row): void {
        $locale = $row['locale'] ?? null;
        $body = $row['body'] ?? null;
        $name = $row['name'] ?? null;

        if ($locale && $locale !== CrawlableRecordSchema::ALL_LOCALES && $body) {
            $this->assertArrayHasKey('body_' . $locale, $row);
        }
        if ($locale && $locale !== CrawlableRecordSchema::ALL_LOCALES && $name) {
            $this->assertArrayHasKey('name_' . $locale, $row);
        }
    }
}
