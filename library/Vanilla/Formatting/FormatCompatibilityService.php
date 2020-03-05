<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Vanilla\EmbeddedContent\Embeds\ErrorEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Quill\Blots\Embeds\ExternalBlot;
use Vanilla\Formatting\Quill\Parser;

/**
 * Transformations of the content of old rich posts into the new format.
 *
 * Currently made up of:
 *
 * Compatibility from the embed refactoring that occured in July 2019
 * https://github.com/vanilla/vanilla/pull/9002
 */
class FormatCompatibilityService {

    /** @var EmbedService */
    private $embedService;

    /**
     * DI
     *
     * @param EmbedService $embedService
     */
    public function __construct(EmbedService $embedService) {
        $this->embedService = $embedService;
    }

    /**
     * Convert old style content to the new format.
     *
     * @param string $content
     * @param string $format
     *
     * @return string
     */
    public function convert(string $content, string $format): string {
        switch ($format) {
            case RichFormat::FORMAT_KEY:
                return $this->convertRich($content);
            default:
                return $content;
        }
    }

    /**
     * We are intentionally not using the full parser here because it is not currently capable
     * of writing back the original format losslessly with modification.
     *
     * @param string $content
     * @return string
     */
    private function convertRich(string $content): string {
        $operations = Parser::jsonToOperations($content);
        foreach ($operations as $key => $operation) {
            if (!ExternalBlot::matches($operation)) {
                continue;
            };
            $data = ExternalBlot::getEmbedDataFromOperation($operation);
            $embed = $this->embedService->createEmbedFromData($data);
            if (!($embed instanceof ErrorEmbed)) {
                setvalr(ExternalBlot::DATA_KEY, $operation, $embed);
                $operations[$key] = $operation;
            }
        }
        return json_encode($operations, JSON_UNESCAPED_UNICODE);
    }
}
