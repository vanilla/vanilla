<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbedUtils;
use Vanilla\Web\Asset\AssetPreloader;
use Vanilla\Web\Asset\ExternalAsset;

/**
 * Embed data object for Panopto.
 */
class PanoptoEmbed extends AbstractEmbed {

    /** @var string JS_SCRIPT */
    const JS_SCRIPT = "https://developers.panopto.com/scripts/embedapi.min.js";

    /** @var string TYPE */
    const TYPE = 'panopto';

    /**
     * PanoptoEmbed constructor.
     *
     * @param array $data
     */
    public function __construct(array $data) {
        parent::__construct($data);

        EmbedUtils::getPreloadModel()->addScript(
            new ExternalAsset(self::JS_SCRIPT),
            AssetPreloader::REL_PRELOAD,
            'panopto-embed-script-asset'
        );
    }

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array {
        return [self::TYPE];
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return Schema::parse([
            "sessionId:s",
            "domain:s",
        ]);
    }
}
