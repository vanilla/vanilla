<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Modules;

use Vanilla\Community\RSSModule;
use Vanilla\Dashboard\Models\RemoteResourceModel;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;

/**
 * Test rendering of the RSS module.
 */
class RSSModuleTest extends StorybookGenerationTestCase {

    use EventSpyTestTrait;
    use CommunityApiTestTrait;

    public static $addons = ['vanilla'];

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
     * Event handler to mount RSS module.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender) {

        /** @var RSSModule $rssModuleGrid */
        $rssModuleGrid = self::container()->get(RSSModule::class);
        $rssModuleGrid->setUrl(url('discussions/feed.rss'));
        $rssModuleGrid->title = 'Grid Display';
        $sender->addModule($rssModuleGrid);
        /** @var RSSModule $rssModuleListItems */
        $rssModuleGridViewAll = self::container()->get(RSSModule::class);
        $rssModuleGridViewAll->setUrl(url('discussions/feed.rss'));
        $rssModuleGridViewAll->setDisplayType(RSSModule::DISPLAY_TILES);
        // View all will appear.
        $rssModuleGridViewAll->setMaxItemCount(1);
        $rssModuleGridViewAll->title = 'Grid Display - Default View All';
        $sender->addModule($rssModuleGridViewAll);
        /** @var RSSModule $rssModuleListItems */
        $rssModuleListItems = self::container()->get(RSSModule::class);
        $rssModuleListItems->setUrl(url('discussions/feed.rss'));
        $rssModuleListItems->setDisplayType(RSSModule::DISPLAY_LIST);
        // View all will appear.
        $rssModuleListItems->setMaxItemCount(1);
        $rssModuleListItems->setViewAllUrl('https://www.test.com');
        $rssModuleListItems->title = 'List items Display - Set View All';
        $sender->addModule($rssModuleListItems);
    }
}
