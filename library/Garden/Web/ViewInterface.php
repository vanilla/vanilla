<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web;

/**
 * Represents a class that can render the result of a dispatch to the output buffer.
 */
interface ViewInterface {
    /**
     * Write the view to the output buffer.
     *
     * @param Data $data The data to render.
     */
    public function render(Data $data);
}
