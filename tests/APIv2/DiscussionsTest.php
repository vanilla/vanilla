<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;


class DiscussionsTest extends AbstractResourceTest {

    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->folder = '/discussions';
        $this->record += ['categoryID' => 1, 'name' => __CLASS__];
        $this->patchFields = array_merge($this->patchFields, ['name', 'categoryID']);

        static::$addons = ['vanilla', 'Htmlawed'];

        parent::__construct($name, $data, $dataName);
    }
}
