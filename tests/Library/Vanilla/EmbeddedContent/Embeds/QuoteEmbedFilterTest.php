<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedFilter;
use VanillaTests\Fixtures\EmbeddedContent\LegacyEmbedFixtures;
use VanillaTests\MinimalContainerTestCase;

/**
 * Test the quote embed filter.
 */
class QuoteEmbedFilterTest extends MinimalContainerTestCase {

    /**
     * Test that user's are properly replace with clean content.
     */
    public function testFiltering() {
        $realUser = $this->getMockUserProvider()->addMockUser();
        $baseData = json_decode(LegacyEmbedFixtures::discussion(), true);
        $baseData['insertUser'] = $this->getMaliciousUserFragment($realUser['userID']);

        /** @var QuoteEmbedFilter $filter */
        $filter = self::container()->get(QuoteEmbedFilter::class);

        $embed = new QuoteEmbed($baseData);
        $embed = $filter->filterEmbed($embed);

        $data = $embed->getData();
        $this->assertSame($realUser, $embed->getData()['insertUser'], "User was not properly replaced");
        $this->assertSame(\Gdn::formatService()->renderHTML($data['bodyRaw'], $data['format']), $data['body'], "Body was not re-rendered");
    }

    /**
     * Get some malicious user fragment that should be overridden.
     *
     * @param int $id The user ID to use.
     *
     * @return array
     */
    private function getMaliciousUserFragment(int $id): array {
        return [
            'name' => 'Fake name!',
            'userID' => $id,
            'photoUrl' => 'test',
            'dateLastActive' => null,
            'label' => $this->getMaliciousBody(),
        ];
    }

    /**
     * @return string
     */
    private function getMaliciousBody(): string {
        return '<script>alert("xss")</script>';
    }
}
