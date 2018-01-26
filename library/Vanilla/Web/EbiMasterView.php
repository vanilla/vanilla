<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Garden\Web\ViewInterface;

/**
 * An view that adds master view support to an **EbiView**.
 */
class EbiMasterView implements ViewInterface {
    /**
     * @var EbiView
     */
    private $view;

    /**
     * @var RequestInterface The current request.
     */
    private $request;

    /**
     * EbiMasterView constructor.
     *
     * @param EbiView $view The view that will do all of the actual rendering.
     * @param RequestInterface $request The current request.
     */
    public function __construct(
        EbiView $view,
        RequestInterface $request
    ) {
        $this->view = $view;
        $this->request = $request;
    }

    /**
     * Write the view to the output buffer.
     *
     * The rendering process involves the following:
     *
     * 1. Pass the data unaltered to the view to render as the main content.
     * 2. Switch the template to the master and render it as a wrapper to the content from step 1.
     *
     * @param Data $data The data to render.
     */
    public function render(Data $data) {
        $master = $data->getMeta('master', 'default-master');

        // See if we should be rendering the master view at all.
        $query = array_change_key_case($this->request->getQuery()) + ['x-asset' => '', 'deliverytype' => ''];
        if ($query['x-master'] === 'view' || $query['deliverytype'] === 'view') {
            $this->view->render($data);
            return;
        }

        // First render the data as its own view.
        ob_start();
        $this->view->render($data);
        $content = ob_get_clean();

        $masterData = new Data([
            'assets' => [ // temp asset array, maybe meta is better.
                'content' => $content
            ],
            'data' => $data->getData(),
        ], $data->getMetaArray());

        // Swap the template for the master template.
        $masterData
            ->setMeta('template', $master)
            ->setMeta('contentTemplate', $data->getMeta('template'));
        $this->view->render($masterData);
    }

    /**
     * Get the view that does the actual rendering.
     *
     * @return EbiView Returns the view.
     */
    public function getView() {
        return $this->view;
    }
}
