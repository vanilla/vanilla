<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedDisplayOptions;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedFilter;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Quill\Parser;
use VanillaTests\Fixtures\EmbeddedContent\LegacyEmbedFixtures;
use VanillaTests\Fixtures\EmbeddedContent\MockEmbed;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\MinimalContainerTestCase;

/**
 * Test the quote embed filter.
 */
class QuoteEmbedFilterTest extends MinimalContainerTestCase {

    use HtmlNormalizeTrait;

    /**
     * Test that user's are properly replace with clean content.
     */
    public function testHtmlFieldFiltering() {
        $realUser = $this->getMockUserProvider()->addMockUser();
        $baseData = json_decode(LegacyEmbedFixtures::discussion(), true);
        $baseData['insertUser'] = $this->getMaliciousUserFragment($realUser['userID']);

        /** @var QuoteEmbedFilter $filter */
        $filter = self::container()->get(QuoteEmbedFilter::class);

        $embed = new QuoteEmbed($baseData);
        $this->assertSame(QuoteEmbed::SECURE_UNRENDERED_MESSAGE, $embed->getData()['insertUser']['label']);
        $this->assertSame(QuoteEmbed::SECURE_UNRENDERED_MESSAGE, $embed->getData()['body']);

        $embed = $filter->filterEmbed($embed);

        $data = $embed->getData();
        $this->assertSame($realUser, $embed->getData()['insertUser'], "User was not properly replaced");
        $this->assertSame(\Gdn::formatService()->renderHTML($data['bodyRaw'], $data['format']), $data['body'], "Body was not re-rendered");
    }

    /**
     * Test content filtering with the minimal display options.
     */
    public function testMinimalContentFiltering() {
        /** @var QuoteEmbedFilter $filter */
        $filter = self::container()->get(QuoteEmbedFilter::class);

        $replacedUrl = 'http://test.com/replaced';

        $quoteData = $this->getQuoteData($replacedUrl, false);

        $embed = new QuoteEmbed($quoteData);
        $filtered = $filter->filterEmbed($embed);

        // Contents replaced with a link.
        $expectedEmbedBodyRaw = [
            [
                'insert' => $replacedUrl,
                'attributes' => [
                    'link' => $replacedUrl,
                ],
            ],
            [ 'insert' => "\n" ],
            [ 'insert' => "After Embed\n" ],
        ];

        $this->assertSame(
            $expectedEmbedBodyRaw,
            Parser::jsonToOperations($filtered->getData()['bodyRaw']),
            'Expected raw embed data to be replaced with a link'
        );

        $expectedRendered = <<<HTML
    <p><a href="http://test.com/replaced" rel="nofollow">http://test.com/replaced</a></p>
    <p>After Embed</p>
HTML;

        $this->assertHtmlStringEqualsHtmlString(
            $expectedRendered,
            $embed->getData()['body'],
            'Expected HTML embed to be replaced with a link'
        );
    }

    /**
     * Test content filtering with the full content display options.
     */
    public function testFullContentFiltering() {
        self::container()->rule(EmbedService::class)
            ->addCall('registerEmbed', [MockEmbed::class, MockEmbed::TYPE]);
        /** @var QuoteEmbedFilter $filter */
        $filter = self::container()->get(QuoteEmbedFilter::class);

        $initialQuoteData = $this->getQuoteData('http://test.com', true);

        $embed = new QuoteEmbed($initialQuoteData);
        $embed = $filter->filterEmbed($embed);

        $expectedEmbedBodyRaw = $initialQuoteData['bodyRaw'];
        $this->assertSame(
            $expectedEmbedBodyRaw,
            Parser::jsonToOperations($embed->getData()['bodyRaw']),
            'Raw body should be unmodified'
        );

        $expectedRendered = <<<HTML
<div
    class="js-embed embedResponsive"
    data-embedJson="{&quot;url&quot;:&quot;http:\/\/test.com&quot;,&quot;embedType&quot;:&quot;testEmbedType&quot;}"
>
    <a href="http://test.com" rel="nofollow noreferrer ugc">
        http://test.com
    </a>
</div><p>After Embed</p>
HTML;

        $this->assertHtmlStringEqualsHtmlString(
            $expectedRendered,
            $embed->getData()['body'],
            'HTML body should be re-rendered fully'
        );
    }

    /**
     * Get some quote data for tests.
     *
     * @param string $embedUrl
     * @param bool $isFullContent
     * @return array
     */
    private function getQuoteData(string $embedUrl, bool $isFullContent): array {
        return [
            'embedType' => QuoteEmbed::TYPE,
            'format' => RichFormat::FORMAT_KEY,
            'body' => '<div><script>alert("This should be replaced!")</script></div>',
            'bodyRaw' => [
                [
                    'insert' => [
                        'embed-external' => [
                            'data' => [
                                'embedType' => MockEmbed::TYPE,
                                'url' => $embedUrl,
                            ],
                        ],
                    ],
                ],
                [ 'insert' => "After Embed\n" ],
            ],
            'url' => 'https://open.vanillaforums.com/discussions/1',
            'recordID' => 25,
            'recordType' => 'comment',
            'dateInserted' => '2018-04-20T21:06:41+00:00',
            'insertUser' => $this->getMockUserProvider()->addMockUser(),
            'displayOptions' => $isFullContent ? QuoteEmbedDisplayOptions::full() : QuoteEmbedDisplayOptions::minimal(false),
        ];
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
