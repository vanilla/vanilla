<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Theme\FontsAsset;
use Vanilla\Theme\HtmlAsset;
use Vanilla\Theme\JsonAsset;
use Vanilla\Theme\StyleAsset;
//use Vanilla\Theme\JavascriptAsset;
use Vanilla\Theme\ScriptsAsset;
use Vanilla\Theme\ImageAsset;

/**
 * ThemesApiController schemes.
 */
trait ThemesApiSchemes {

    /**
     * Result theme schema
     *
     * @param string $type
     * @return Schema
     */
    private function themeResultSchema(string $type = 'out'): Schema {
        $schema = $this->schema(
            Schema::parse([
                'themeID:s',
                'type:s',
                'name:s?',
                'version:s?',
                'current:b?',
                'assets?' => $this->assetsSchema()
            ]),
            $type
        );
        return $schema;
    }

    /**
     * Get 'assets' schema
     *
     * @return Schema
     */
    private function assetsSchema(): Schema {
        $schema = Schema::parse([
            "header?" => new InstanceValidatorSchema(HtmlAsset::class),
            "footer?" => new InstanceValidatorSchema(HtmlAsset::class),
            "variables?" => new InstanceValidatorSchema(JsonAsset::class),
            "fonts?" => new InstanceValidatorSchema(FontsAsset::class),
            "scripts?" => new InstanceValidatorSchema(ScriptsAsset::class),
            "styles:s?",
            "javascript:s?",
            "logo?" => new InstanceValidatorSchema(ImageAsset::class),
            "mobileLogo?" => new InstanceValidatorSchema(ImageAsset::class),
        ])->setID('themeAssetsSchema');
        return $schema;
    }

    /**
     * POST theme schema
     *
     * @param string $type
     * @return Schema
     */
    private function themePostSchema(string $type = 'in'): Schema {
        $schema = $this->schema(
            Schema::parse([
                'name:s' => [
                    'description' => 'Custom theme name.',
                ],
            ]),
            $type
        );
        return $schema;
    }

    /**
     * PUT current theme schema
     *
     * @param string $type
     * @return Schema
     */
    private function themePutCurrentSchema(string $type = 'in'): Schema {
        $schema = $this->schema(
            Schema::parse([
                'themeID:i' => [
                    'description' => 'Theme ID.',
                ],
            ]),
            $type
        );
        return $schema;
    }

    /**
     * PUT 'assets' schema
     *
     * @return Schema
     */
    private function assetsPutSchema(): Schema {
        $schema = Schema::parse([
            "data:s",
        ])->setID('themeAssetsPutSchema');
        return $schema;
    }
}
