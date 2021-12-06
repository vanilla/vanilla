<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

/**
 * Tests for api/v2/layouts endpoints
 */
class LayoutsApiControllerTest extends AbstractResourceTest {

    //region Properties
    /** {@inheritdoc} */
    protected $baseUrl = '/layouts';

    /** {@inheritdoc} */
    protected $pk = 'layoutID';

    /** {@inheritdoc} */
    protected $editFields = ['name', 'layout'];

    /** {@inheritdoc} */
    protected $patchFields = ['name', 'layout'];

    /** {@inheritdoc} */
    protected $record = ['name' => 'Layout', 'layout' => '{"foo" => "bar"}', 'layoutType' => 'homepage'];

    //endregion

    //region Setup / Teardown
    //endregion

    //region Test Methods / Data Providers
    //endregion
}
