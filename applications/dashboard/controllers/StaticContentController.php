<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Data;

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

    /**
     * Output source maps of static js resources if allowed (Garden.Security.SourceMaps.Enabled)
     */
    public function sourcemaps() {
        $sourceMapsEnabled = Gdn::config()->get(
            'Garden.Security.SourceMaps.Enabled',
            Gdn::config()->get('Garden.Debug')
        );
        $this->MasterView = 'none';
        $this->setHeader("Content-Type", 'application/json');

        if ($sourceMapsEnabled) {
            $path = ltrim(substr(Gdn::request()->path(), strlen('staticcontent/sourcemap/')), DS);
            $fullPath = PATH_ROOT.DS.'dist'.DS.$path;
            if (is_file($fullPath)) {
                $content = file_get_contents($fullPath);
                $this->setData('sourcemap', $content);
            } else {
                $this->statusCode(404, 'File not found'.' '.$path);
            }
        } else {
            $this->statusCode(403);
        }
        $this->render();
    }
}
