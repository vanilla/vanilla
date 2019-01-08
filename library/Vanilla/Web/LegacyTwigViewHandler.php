<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Vanilla\Contracts\Web\LegacyViewHandlerInterface;

/**
 * Gdn_Controller view handler for smarty.
 */
class LegacyTwigViewHandler implements LegacyViewHandlerInterface {
    use \Garden\TwigTrait;

    /**
     * @inheritdoc
     *
     * @param string $path
     * @param \Gdn_Controller $controller
     */
    public function render($path, $controller) {
        $path = str_replace(PATH_ROOT, '', $path);
        echo self::twigInit()->render($path, $controller->Data);
    }
}
