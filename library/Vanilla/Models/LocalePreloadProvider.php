<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\Data;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionProviderInterface;

/**
 * Page preloader for locale information.
 */
class LocalePreloadProvider implements ReduxActionProviderInterface {

    /** @var \LocalesApiController */
    private $localesApi;

    /**
     * DI.
     *
     * @param \LocalesApiController $localesApi
     */
    public function __construct(\LocalesApiController $localesApi) {
        $this->localesApi = $localesApi;
    }


    /**
     * @inheritdoc
     */
    public function createActions(): array {
        $locales = $this->localesApi->index();

        return [
            new ReduxAction(
                \LocalesApiController::GET_ALL_REDUX_KEY,
                Data::box($locales),
                []
            ),
        ];
    }
}
