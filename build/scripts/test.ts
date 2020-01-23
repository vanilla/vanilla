/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getOptions } from "./buildOptions";
import { KarmaRunner } from "./KarmaRunner";

/**
 * Run the build. Options are passed as arguments from the command line.
 * @see https://docs.vanillaforums.com/developer/tools/building-frontend/
 */
void getOptions().then(options => {
    const runner = new KarmaRunner(options);
    return runner.run();
});
