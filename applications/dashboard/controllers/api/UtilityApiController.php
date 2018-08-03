<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Dashboard\Api;

use DashboardNavModule;
use Garden\Schema\Schema;
use Vanilla\Formatting\Quill\Parser;
use Vanilla\Formatting\Quill\Renderer;

/**
 * Contains information useful for building the dashboard.
 */
class UtilityApiController extends \AbstractApiController {

    /**
     * Get the menus in the dashboard.
     *
     * @return array Returns the menus.
     */
    public function post_render(array $body) {
        // This is the array of permissions from the module.
        // We just want to make sure the user has at least one of the permissions although if they don't then the menus would be empty.

        $in = $this->schema(Schema::parse(['content:s' => 'The JSON encoded contents to render']), 'in')
            ->setDescription('Render a quill delta.')
        ;
        $out = $this->schema(Schema::parse([
            'body:s' => 'The html body',
        ]), 'out');

        $body = $in->validate($body);

        $result = [
            'body' => \Gdn_Format::richQuote($body['content']),
        ];

        $result = $out->validate($result);

        return $result;
    }
}
