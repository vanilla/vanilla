<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;


class DiscussionsTest extends AbstractResourceTest {

    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/discussions';
        $this->record += ['categoryID' => 1, 'name' => __CLASS__];

        parent::__construct($name, $data, $dataName);
    }
}
