<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * An autoloader for vanilla class aliases.
 *
 * We need this because declaring a class_alias autoloads the class.
 * Declaring aliases up front (like in the bootstrap) would autoload all of our classes.
 * This class provides an autoloader for usage with spl_autoload_register to autoload these aliases
 * which will then autoload their new classes if they are not loaded yet.
 */
class AliasLoader {

    use AliasProviderTrait;

    /**
     * @inheritdoc
     */
    protected static function provideAliases(): array {
        return [
            \Vanilla\Web\Asset\LegacyAssetModel::class => ['AssetModel'],
            \Vanilla\Dashboard\Models\BannerImageModel::class => ['HeroImagePlugin'],
        ];
    }
}
