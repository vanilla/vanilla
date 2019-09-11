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

    use TwigRenderTrait;

    /**
     * Render the given view.
     *
     * @param string $path
     * @param \Gdn_Controller $controller
     */
    public function render($path, $controller) {
        // Extra data to merge into the controllers data for every render.
        $extraData = [
            'form' => $controller->Form ? new TwigFormWrapper($controller->Form) : null,
            'category' => $controller->Category ?? null,
            'discussion' => $controller->Discussion ?? null,
        ];

        $data = array_merge($extraData, (array) $controller->Data);
        // We need to echo instead of return returning because \Gdn_Controller::fetchView()
        // uses only ob_start and ob_get_clean to gather the rendered result.
        echo $this->renderTwig($path, $data);
    }
}
