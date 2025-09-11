<?php

use Garden\Container\Reference;
use IPBFormatter\Formats\IPBFormat;
use IPBFormatter\Formatter;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\FormatService;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;
use VanillaTests\Library\Vanilla\Formatting\Formats\AbstractFormatTestCase;
use VanillaTests\Library\Vanilla\Formatting\UserMentionTestTraits;

/**
 * Test for the IPB format.
 *
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */
class IPBFormatterTest extends AbstractFormatTestCase
{
    use UserMentionTestTraits;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()
            ->rule(FormatService::class)
            ->addCall("registerFormat", [IPBFormat::FORMAT_KEY, new Reference(IPBFormat::class)]);

        $this->container()
            ->rule(Formatter::class)
            ->addAlias("IPBFormatter")
            ->addAlias("ipbFormatter")
            ->setShared(true);
    }

    /**
     * @inheritdoc
     */
    protected function prepareFormatter(): FormatInterface
    {
        return self::container()->get(IPBFormat::class);
    }

    /**
     * @inheritdoc
     */
    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory(IPBFormat::FORMAT_KEY))->getAllFixtures();
    }
}
