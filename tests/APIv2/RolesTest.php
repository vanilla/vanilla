<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/roles endpoints.
 */
class RolesTest extends AbstractResourceTest {

    protected $editFields = ['canSession', 'deletable', 'description', 'name', 'personalInfo', 'type'];

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/roles';
        $this->record = [
            'name' => 'Tester',
            'description' => 'Diligent QA workers.',
            'type' => 'member',
            'deletable' => true,
            'canSession' => true,
            'personalInfo' => false
        ];

        parent::__construct($name, $data, $dataName);
    }
}