/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { setupMobileNavigation } from "./mobileNavigation";

$(() => {
    setupMobileNavigation();

    $("select").wrap('<div class="SelectWrapper"></div>');
});
