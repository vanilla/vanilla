/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getOptions } from "./buildOptions";
import Builder from "./Builder";

/**
 * Run just the install phase of the build.
 */
void getOptions().then(options => {
    const builder = new Builder(options);
    return builder.installOnly();
});
