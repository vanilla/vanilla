<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures\EmbeddedContent;

use Garden\Schema\Schema;
use Vanilla\Contracts\Formatting\Heading;
use Vanilla\Contracts\Formatting\HeadingProviderInterface;
use Vanilla\EmbeddedContent\AbstractEmbed;

class MockHeadingProviderEmbed extends AbstractEmbed implements HeadingProviderInterface
{
    const TYPE = "mockHeadingProviderEmbed";

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array
    {
        return [self::TYPE];
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema
    {
        return Schema::parse(["headings:a"]);
    }

    /**
     * Get headings from the data array.
     *
     * @return array
     */
    public function getHeadings(): array
    {
        $headings = $this->data["headings"] ?? [];

        $results = [];
        foreach ($headings as $heading) {
            $results[] = new Heading($heading["text"], $heading["level"], $heading["ref"]);
        }
        return $results;
    }
}
