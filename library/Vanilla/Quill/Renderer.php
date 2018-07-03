<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill;

use Vanilla\Quill\Blots;

/**
 * Class for rendering BlotGroups into HTML
 *
 * @see BlotGroup
 *
 * @package Vanilla\Quill
 */
class Renderer {

    /** @var Parser */
    private $parser;

    public function __construct(Parser $parser) {
        $this->parser = $parser;
    }

    /**
     * Render operations into HTML.
     *
     * @return string
     */
    public function render(array $operations): string {
        $groups = $this->parser->parse($operations);
        $result = "";
        $previousGroupEndsWithBlockEmbed = false;
        $previousGroupIsBreakOnly = false;
        foreach ($groups as $index => $group) {
            $skip = false;
            $isLastPosition = $index === count($groups) - 1;

            if ($group->isBreakOnlyGroup()) {
                if ($previousGroupIsBreakOnly) {
                    // Skip if the this is last Break in a series of 2+ breaks.
                    if (!$isLastPosition) {
                        $nextGroup = $groups[$index + 1];
                        if (!$nextGroup->isBreakOnlyGroup()) {
                            $skip = true;
                        }
                    }

                    // If there are multiple breaks at the end of the delta, the last one doesn't render.
                    if ($isLastPosition) {
                        $skip = true;
                    }
                }

                // Skip the last line break unless the previous group was a block embed.
                if ($isLastPosition && !$previousGroupEndsWithBlockEmbed) {
                    $skip = true;
                }
            }

            // Update previous group values.
            $previousGroupIsBreakOnly = $group->isBreakOnlyGroup();
            $previousGroupEndsWithBlockEmbed = $group->endsWithBlotOfType(Blots\Embeds\AbstractBlockEmbedBlot::class);

            // Render unless we decided we had to sip this group.
            if (!$skip) {
                $result .= $group->render();
            }
        }

        return $result;
    }
}
