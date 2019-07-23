<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Class StaticContentController
 */
class StaticContentController extends Gdn_Controller {
    use \Vanilla\Web\TwigRenderTrait;

    /**
     * Generate the container.html body. This page initialize easyXDM library to provide a way to embed vanilla pages.
     */
    public function container() {
        $this->MasterView = 'none';
        $this->setData(['content' => $this->renderTwig('/applications/dashboard/views/staticcontent/container.twig', [])]);
        $this->render();
    }
}
