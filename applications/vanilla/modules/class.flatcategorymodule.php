<?php

class FlatCategoryModule extends CategoryFilterModule {

    const DEFAULT_LIMIT = 10;

    public $view = 'categoryfilter';

    public function __construct() {
        deprecated('FlatCategoryModule', 'CategoryFilterModule', 'February 2017');
        parent::__construct();
    }
}
