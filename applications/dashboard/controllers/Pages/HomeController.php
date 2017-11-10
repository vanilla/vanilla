<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Dashboard\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Web\Controller;
use Vanilla\Web\EbiView;

class HomeController extends Controller {
    /**
     * @var EbiView
     */
    private $view;

    public function __construct(EbiView $view) {
        $this->view = $view;
    }

    public function index() {
        $data = $this->view->getData('home');

        return new Data($data, ['template' => 'home-page']);
    }
}
