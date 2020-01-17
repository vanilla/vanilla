<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Vanilla\Theme\TwigAsset;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Theme\FontsAsset;
use Vanilla\Theme\HtmlAsset;
use Vanilla\Theme\JsonAsset;
use Vanilla\Theme\StyleAsset;
//use Vanilla\Theme\JavascriptAsset;
use Vanilla\Theme\ScriptsAsset;
use Vanilla\Theme\ImageAsset;
use Vanilla\Models\ThemeModel;

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
                'parentTheme:s?',
                'assets?' => $this->assetsSchema(),
            ]),
            $type
        );
        return $schema;
    }

    /**
     * Result theme schema
     *
     * @param string $type
     * @return Schema
     */
    private function themesResultSchema(string $type = 'out'): Schema {
        $schema = $this->themeResultSchema()->merge(
            Schema::parse(
                [
                    'preview?' => [":a" => $this->assetsPreviewSchema()]
                ]
            )
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
            "header?" => new InstanceValidatorSchema([HtmlAsset::class, TwigAsset::class]),
            "footer?" => new InstanceValidatorSchema([HtmlAsset::class, TwigAsset::class]),
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
                'parentTheme:s' => [
                    'description' => 'Parent theme template name.',
                ],
                'parentVersion:s' => [
                    'description' => 'Parent theme template version/revision.',
                ],
                'assets?' => Schema::parse([
                    "header?" => $this->assetsPutArraySchema(),
                    "footer?" => $this->assetsPutArraySchema(),
                    "variables:s?",
                    "fonts:s?",
                    "scripts:s?",
                    "styles:s?",
                    "javascript:s?"
                ])
                    ->addValidator('header', [ThemeModel::class, 'validator'])
                    ->addValidator('footer', [ThemeModel::class, 'validator'])
                    ->addValidator('variables', [ThemeModel::class, 'validator'])
                    ->addValidator('fonts', [ThemeModel::class, 'validator'])
                    ->addValidator('scripts', [ThemeModel::class, 'validator'])
            ]),
            $type
        );
        return $schema;
    }

    /**
     * PATCH theme schema
     *
     * @param string $type
     * @return Schema
     */
    private function themePatchSchema(string $type = 'in'): Schema {
        $schema = $this->schema(
            Schema::parse([
                'name:s?' => [
                    'description' => 'Custom theme name.',
                ],
                'parentTheme:s?' => [
                    'description' => 'Parent theme template name.',
                ],
                'parentVersion:s?' => [
                    'description' => 'Parent theme template version/revision.',
                ],
                'assets?' => Schema::parse([
                    "header?" => $this->assetsPutArraySchema(),
                    "footer?" => $this->assetsPutArraySchema(),
                    "variables:s?",
                    "fonts:s?",
                    "scripts:s?",
                    "styles:s?",
                    "javascript:s?"
                ])
                    ->addValidator('header', [ThemeModel::class, 'validator'])
                    ->addValidator('footer', [ThemeModel::class, 'validator'])
                    ->addValidator('variables', [ThemeModel::class, 'validator'])
                    ->addValidator('fonts', [ThemeModel::class, 'validator'])
                    ->addValidator('scripts', [ThemeModel::class, 'validator'])
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
                'themeID:s' => [
                    'description' => 'Theme ID or Theme Key',
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

    /**
     * PUT 'assets' schema
     *
     * @return Schema
     */
    private function assetsPutArraySchema(): Schema {
        $schema = Schema::parse([
            "data",
            "type"
        ])->setID('themeAssetsPutSchema');
        return $schema;
    }

    /**
     * PUT 'assets' schema
     *
     * @return Schema
     */
    private function assetsPreviewSchema(): Schema {
        $schema = Schema::parse([
            "global.mainColors.primary:s?",
            "global.mainColors.bg:s?",
            "global.mainColors.fg:s?",
            "titleBar.colors.bg:s?",
            "titleBar.colors.fg:s?",
            "splash.outerBackground.image:s?",
            "theme.preview.image:s?",
        ])->setID('themeAssetsPreviewSchema');
        return $schema;
    }
}
