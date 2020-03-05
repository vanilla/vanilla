<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Forum\Menu\UserCounterProvider;
use Vanilla\Menu\Counter;

/**
 * Test the CounterProviders
 */
class CounterProvidersTest extends AbstractAPIv2Test {
    /**
    * {@inheritdoc}
    */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
    }
    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();
    }

    /**
     * Test ConversationCounterProvider.
     */
    public function testConversationCounterProvider() {
        $session = self::container()->get(\Gdn_Session::class);

        $provider = new \ConversationCounterProvider($session);

        $counters = $provider->getMenuCounters();

        $this->assertTrue(is_array($counters));
        $this->assertEquals(1, count($counters));

        $counter = $counters[0];

        $this->assertInstanceOf(Counter::class, $counter);
        $this->assertEquals('Conversations', $counter->getName());
    }

    /**
     * Test UserCounterProvider.
     */
    public function testUserCounterProvider() {
        $session = self::container()->get(\Gdn_Session::class);

        $provider = new UserCounterProvider($session);

        $counters = $provider->getMenuCounters();

        $this->assertTrue(is_array($counters));
        $this->assertEquals(4, count($counters));

        foreach ($counters as $counter) {
            $this->assertInstanceOf(Counter::class, $counter);
            $this->assertTrue(in_array($counter->getName(), ['Discussions', 'Bookmarks', 'UnreadDiscussions', 'Drafts']));
        }
    }

    /**
     * Test RoleCounterProvider.
     */
    public function testRoleCounterProvider() {
        $session = self::container()->get(\Gdn_Session::class);

        $provider = new \RoleCounterProvider(self::container()->get(\RoleModel::class), $session);

        $counters = $provider->getMenuCounters();

        $this->assertTrue(is_array($counters));
        $this->assertEquals(1, count($counters));

        $counter = $counters[0];
        $this->assertInstanceOf(Counter::class, $counter);
        $this->assertEquals('Applicants', $counter->getName());
    }

    /**
     * Test LogCounterProvider.
     */
    public function testLogCounterProvider() {
        $session = self::container()->get(\Gdn_Session::class);

        $provider = new \LogCounterProvider(self::container()->get(\LogModel::class), $session);

        $counters = $provider->getMenuCounters();

        $this->assertTrue(is_array($counters));
        $this->assertEquals(2, count($counters));

        foreach ($counters as $counter) {
            $this->assertInstanceOf(Counter::class, $counter);
            $this->assertTrue(in_array($counter->getName(), ['SpamQueue', 'ModerationQueue']));
        }
    }

    /**
     * Test ActivityCounterProvider.
     */
    public function testActivityCounterProvider() {
        $session = self::container()->get(\Gdn_Session::class);

        $provider = new \ActivityCounterProvider(self::container()->get(\ActivityModel::class), $session);

        $counters = $provider->getMenuCounters();

        $this->assertTrue(is_array($counters));
        $this->assertEquals(1, count($counters));

        $counter = $counters[0];
        $this->assertInstanceOf(Counter::class, $counter);
        $this->assertEquals('UnreadNotifications', $counter->getName());
    }
}
