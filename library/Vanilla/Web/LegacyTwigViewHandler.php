<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Vanilla\Contracts\Web\LegacyViewHandlerInterface;

/**
 * Gdn_Controller view handler for twig.
 */
class LegacyTwigViewHandler implements LegacyViewHandlerInterface {
    use \Garden\TwigTrait;

    /** @var \Twig_Environment */
    private $twig;

    /**
     * Initialize the twig environment.
     */
    public function __construct() {
        $this->twig = self::twigInit();
    }

    /**
     * Render the given view.
     *
     * @param string $path
     * @param \Gdn_Controller $controller
     */
    public function render($path, $controller) {
        $path = str_replace(PATH_ROOT, '', $path);

        // We need to echo instead of return returning because \Gdn_Controller::fetchView()
        // uses only ob_start and ob_get_clean to gather the rendered result.
         echo $this->twig->render($path, $controller->Data);
    }
}
