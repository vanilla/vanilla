/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const fs = require("fs");
const path = require("path");

const vanillaEnv = path.resolve(__dirname, "../../../environment.php");
if (!fs.existsSync(vanillaEnv)) {
    console.error("Do not run yarn commands directly in this directory.\nInstead run them with `yarn workspace theme-key command`.");
    process.exit(1);
}