<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Modules;

use Vanilla\Community\RSSModule;
use Vanilla\Dashboard\Models\RemoteResourceModel;
use Vanilla\Widgets\AbstractHomeWidgetModule;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;

/**
 * Test rendering of the RSS module.
 */
class RSSModuleStorybookTest extends StorybookGenerationTestCase {

    use EventSpyTestTrait;
    use CommunityApiTestTrait;
    use LayoutTestTrait;

    /**
     * Configure the container.
     */
    public function setUp(): void {
        parent::setUp();
        // Put some data to avoid call remote resource job.
        $stub = $this->createStub(RemoteResourceModel::class);
        $dataRSS = '<rss xmlns:media="https://vanillaforums.com/rss/" version="2.0">
                <channel>
                    <link>https://vanillaforums.com/channel</link>
                    <title>Channel</title>
                    <description>Channel description</description>
                    <item>
                        <link>https://vanillaforums.com/title-1</link>
                        <pubDate>Fri, 19 Feb 2021 17:50:40 GMT</pubDate>
                        <title>Title 1</title>
                        <description>
                        <![CDATA[ <img src="https://us.v-cdn.net/5022541/uploads/091/7G8KTIZCJU5S.jpeg" alt="An image" title="Image Title" /><p>Description.</p> ]]>
                        </description>
                    </item>
                    <item>
                        <link>https://vanillaforums.com/title-2</link>
                        <pubDate>Thu, 18 Feb 2021 18:35:40 GMT</pubDate>
                        <title><![CDATA[ Title 2]]></title>
                        <description>Description 3</description>
                    </item>
                </channel>
            </rss>';
        $stub->method('getByUrl')
            ->willReturn($dataRSS)
        ;

        self::container()->setInstance(RemoteResourceModel::class, $stub);
    }

    /**
     * Test rendering of the RSS module.
     */
    public function testRender() {
        $this->generateStoryHtml('/', 'RSS Module');
    }

    /**
     * Test that we can hydrate an RSS widget.
     */
    public function testHydrateRssWidget() {
        $fallbackImageUrl = 'https://images.com/fallback.png';
        $apiParams = [
            'feedUrl' => '/discussions/feed.rss',
            'fallbackImageUrl' => $fallbackImageUrl,
            'limit' => 3
        ];
        $containerOptions = [
            'borderType' => 'shadow',
            'viewAll' => [
                'to' => 'https://someplace.com',
            ],
        ];
        $spec = [
            '$hydrate' => 'react.rss',
            'apiParams' => $apiParams,
            'title' => 'My RSS Feed',
            'containerOptions' => $containerOptions,
        ];

        $hydrateParams = [];

        $expected = [
            '$reactComponent' => 'RSSWidget',
            '$reactProps' => [
                'apiParams' => $apiParams,
                'title' => 'My RSS Feed',
                'itemData' => [
                    [
                        'to' => 'https://vanillaforums.com/title-1',
                        'name' => 'Title 1',
                        'imageUrl' => 'https://us.v-cdn.net/5022541/uploads/091/7G8KTIZCJU5S.jpeg',
                        'description' => 'Description.',
                    ],
                    [
                        'to' => 'https://vanillaforums.com/title-2',
                        'name' => 'Title 2',
                        'imageUrl' => $fallbackImageUrl,
                        'description' => 'Description 3',
                    ],
                ],
                'containerOptions' => $containerOptions,
            ],
        ];
        $this->assertHydratesTo($spec, $hydrateParams, $expected);
    }

    /**
     * Event handler to mount RSS module.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender) {
        /** @var RSSModule $rssModuleDefault */
        $rssModuleDefault = self::container()->get(RSSModule::class);
        $rssModuleDefault->setUrl(url('discussions/feed.rss'));
        $rssModuleDefault->title = 'RSS Feed - Default';
        $sender->addModule($rssModuleDefault);
        // View all will appear.
        /** @var RSSModule $rssModuleSetViewAll */
        $rssModuleSetViewAll = self::container()->get(RSSModule::class);
        $rssModuleSetViewAll->setUrl(url('discussions/feed.rss'));
        $rssModuleSetViewAll->setMaxItemCount(1);
        $rssModuleSetViewAll->setViewAllUrl('https://www.test.com');
        $rssModuleSetViewAll->title = 'RSS Feed - Set View All URL';
        $sender->addModule($rssModuleSetViewAll);
    }
}
