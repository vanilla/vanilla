/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const autoprefixer = require("autoprefixer");

module.exports = ({ options }) => {
    const { isLegacy } = options;
    const legacyBrowserList = "ie > 10, last 4 versions, not dead, safari 8";
    const modernBrowserList = "Edge >= 83, Firefox >= 78, FirefoxAndroid >= 78, Chrome >= 80, ChromeAndroid >= 80, Opera >= 67, OperaMobile >= 67, Safari >= 13.1, iOS >= 13.4";

    const browsers = () => {
        return (isLegacy ? legacyBrowserList : modernBrowserList).replace(/,\s/gi, ',').split(',');
    }

    return {
        plugins: [autoprefixer({ overrideBrowserslist: browsers() })],
    }
};
