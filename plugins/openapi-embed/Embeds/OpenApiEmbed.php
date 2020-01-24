<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Addons\OpenApiEmbed\Embeds;

use Garden\Schema\Schema;
use Vanilla\Contracts\Formatting\Heading;
use Vanilla\Contracts\Formatting\HeadingProviderInterface;
use Vanilla\EmbeddedContent\AbstractEmbed;

class OpenApiEmbed extends AbstractEmbed implements HeadingProviderInterface {

    const TYPE = "openapi";

    protected function getAllowedTypes(): array {
        return [self::TYPE];
    }

    protected function schema(): Schema {
        return Schema::parse([
            'headings:a?',
        ]);
    }

    public function getHeadings(): array {
        $headings = $this->data['headings'] ?? [];

        $results = [];
        foreach ($headings as $heading) {
            $results[] = new Heading($heading['text'], $heading['level'], $heading['ref']);
        }
        return $results;
    }
}
