/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const resolve = require("path").resolve;
const VANILLA_ROOT = resolve(path.join(__dirname, "../"));

module.exports = {
    resolve: {
        alias: {
            // SCSS
            "library-scss": resolve(VANILLA_ROOT, "library/src/scss/"),
        },
    }
}
