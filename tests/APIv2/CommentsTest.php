<?php
/**
 * @author Ryan Perry <ryan.p@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\APIv2;

/**
 * Test the /api/v2/discussions endpoints.
 */
class CommentsTest extends AbstractResourceTest {

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '') {
        $this->baseUrl = '/comments';
        $this->record += ['discussionID' => 1];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * @inheritdoc
     */
    public function indexUrl() {
        $indexUrl = $this->baseUrl;
        $indexUrl .= '?'.http_build_query(['discussionID' => 1]);
        return $indexUrl;
    }
}
