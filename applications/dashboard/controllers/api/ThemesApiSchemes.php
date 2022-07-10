<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use PHPUnit\Framework\MockObject\Api;
use Vanilla\ApiUtils;
use Vanilla\Theme\Theme;
use Vanilla\Theme\Asset;
use Vanilla\Theme\ThemeAssetFactory;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Theme\ThemeService;

/**
 * ThemesApiController schemes.
 */
trait ThemesApiSchemes {

    /**
     * Result theme schema
     *
     * @return Schema
     */
    private function themeResultSchema(): Schema {
        return new InstanceValidatorSchema(Theme::class);
    }

    /**
     * @return Schema
     */
    private function assetExpandDefinition(): Schema {
        $assetNames = array_keys(ThemeAssetFactory::DEFAULT_ASSETS);
        $keys = array_map(function ($key) {
            return $key.'.data';
        }, $assetNames);
        return ApiUtils::getExpandDefinition($keys);
    }

    /**
     * Get 'assets' schema
     *
     * @return Schema
     */
    private function assetsSchema(): Schema {
        $schema = Schema::parse([
            "header?" => new InstanceValidatorSchema([Asset\HtmlThemeAsset::class, Asset\TwigThemeAsset::class]),
            "footer?" => new InstanceValidatorSchema([Asset\HtmlThemeAsset::class, Asset\TwigThemeAsset::class]),
            "variables?" => new InstanceValidatorSchema(Asset\JsonThemeAsset::class),
            "fonts?" => new InstanceValidatorSchema(Asset\JsonThemeAsset::class),
            "scripts?" => new InstanceValidatorSchema(Asset\JsonThemeAsset::class),
            "styles:s?" => new InstanceValidatorSchema(Asset\CssThemeAsset::class),
            "javascript:s?" => new InstanceValidatorSchema(Asset\JavascriptThemeAsset::class),
            "logo?" => new InstanceValidatorSchema(Asset\ImageThemeAsset::class),
            "mobileLogo?" => new InstanceValidatorSchema(Asset\ImageThemeAsset::class),
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
                    "header?" => $this->assetInputSchema('header'),
                    "footer?" => $this->assetInputSchema('footer'),
                    "variables?" => $this->assetInputSchema('variables'),
                    "fonts?" => $this->assetInputSchema('fonts'),
                    "scripts?" => $this->assetInputSchema('scripts'),
                    "styles?" => $this->assetInputSchema('styles'),
                    "javascript?" => $this->assetInputSchema('javascript')
                ])
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
                'revisionID:i?' => [
                    'description' => 'Theme revision ID.',
                ],
                'revisionName:s?' => [
                    'description' => 'Theme revision name.',
                ],
                'assets?' => Schema::parse([
                    "header?" => $this->assetInputSchema('header'),
                    "footer?" => $this->assetInputSchema('footer'),
                    "variables?" => $this->assetInputSchema('variables'),
                    "fonts?" => $this->assetInputSchema('fonts'),
                    "scripts?" => $this->assetInputSchema('scripts'),
                    "styles?" => $this->assetInputSchema('styles'),
                    "javascript?" => $this->assetInputSchema('javascript')
                ])
            ]),
            $type
        );
        return $schema;
    }

    /**
     * Schema for asset arrays.
     *
     * @param string $fieldName The name of the field.
     *
     * @return Schema
     */
    public function assetInputSchema(string $fieldName): Schema {
        $schema = $this->schema([
            'type:s',
            'data:s|o' => [
                'minLength' => 0,
            ]
        ]);
        $schema->addValidator('', function ($data, ValidationField $field) {
            $type = $data['type'] ?? null;
            $dataData = $data['data'] ?? null;
            if ($type !== ThemeAssetFactory::ASSET_TYPE_JSON && is_array($dataData)) {
                $field->addError('Objects for the `data` field are only supported when the type is `json`.');
            }
        });
        $schema->addFilter('data', function ($data) {
            if (is_array($data)) {
                return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                return $data;
            }
        });
        /** @var ThemeAssetFactory $assetFactory */
        $assetFactory = $this->assetFactory;
        $schema->addValidator('', function ($data, ValidationField $field) use ($assetFactory, $fieldName) {
            $type = $data['type'] ?? null;
            $dataData = $data['data'] ?? null;

            if ($dataData === null || $type === null) {
                // Will get caught in the normal validation.
                return;
            }

            try {
                $asset = $assetFactory->createAsset(null, $type, $fieldName, $dataData, true);
                $asset->validate();
            } catch (Exception $e) {
                $field->addError($e->getMessage());
            }
        });
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
     * PUT preview theme schema
     *
     * @param string $type
     * @return Schema
     */
    private function themePutPreviewSchema(string $type = 'in'): Schema {
        $schema = $this->schema(
            Schema::parse([
                'themeID:s?' => [
                    'description' => 'Theme ID or Theme Key',
                ],
                'revisionID:i?' => [
                    'description' => 'Theme revision ID',
                ],
            ]),
            $type
        );
        return $schema;
    }
}
