<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\VariableProviders;

use Garden\Schema\Schema;
use Vanilla\Web\JsInterpop\AbstractReactModule;

/**
 * Module for the react based quicklinks.
 */
class QuickLinksModule extends AbstractReactModule {

    /**
     * @inheritdoc
     */
    public function getProps(): ?array {
        return [
            'title' => t('Quick Links'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getComponentName(): string {
        return 'QuickLinks';
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        return Schema::parse([]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return 'Quick Links';
    }
}
