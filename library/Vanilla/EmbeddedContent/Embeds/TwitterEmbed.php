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
use Vanilla\Web\Asset\AssetPreloadModel;
use Vanilla\Web\Asset\ExternalAsset;

/**
 * Embed data object for Twitter.
 */
class TwitterEmbed extends AbstractEmbed {

    const JS_SCRIPT = "https://platform.twitter.com/widgets.js";
    const TYPE = "twitter";

    /**
     * Override to set a value in the PreloadAssetModel.
     * @inheritdoc
     */
    public function __construct(array $data) {
        parent::__construct($data);

        // The twitter embed causes the page scrolling to jump if we don't load it's scripts in the initial page load.
        // Even a normal preload still hases scroll anchoring issues.
        // See https://github.com/vanilla/vanilla/issues/8884
        EmbedUtils::getPreloadModel()->addScript(
            new ExternalAsset(self::JS_SCRIPT),
            AssetPreloader::REL_FULL,
            'twitter-embed-script-asset'
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
    public function normalizeData(array $data): array {
        $data = EmbedUtils::remapProperties($data, [
            "statusID" => "attributes.statusID",
        ]);
        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return Schema::parse([
            "statusID:s",
        ]);
    }
}
