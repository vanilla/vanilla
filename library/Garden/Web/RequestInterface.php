<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Web;


interface RequestInterface {
    public function getPath();

    public function getMethod();

    public function getQuery();

    public function getBody();
}