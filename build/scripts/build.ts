/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import webpack, { Stats } from "webpack";
import { makeProdConfig } from "./makeProdConfig";

void makeProdConfig().then(config => {
    const compiler = webpack(config);
    const logger = console;
    compiler.run((err: Error, stats: Stats) => {
        if (err) {
            logger.error("The build encountered an error:" + err);
        }

        logger.log(
            stats.toString({
                chunks: false, // Makes the build much quieter
                modules: false,
                colors: true, // Shows colors in the console
            }),
        );
    });
});
