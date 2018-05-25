const fs = require("fs");
const path = require("path");

/**
 * @param {string} moduleDirectory
 */
function verifyModuleInstallation(moduleDirectory) {
    const nonModuleRoot = moduleDirectory.replace("node_modules", "");
    const packageJsonPath = path.join(nonModuleRoot, "package.json");
    console.log(nonModuleRoot, packageJsonPath);
    if (
        fs.existsSync(packageJsonPath) &&
        (!fs.existsSync(moduleDirectory) || fs.readdirSync(moduleDirectory).length === 0)
    ) {
        console.error();
        console.error(`It seems you forgot to install the node_modules for the directory ${nonModuleRoot}`);
        console.error("Change to that directory and run `yarn install`");
        console.error();
        process.exit(0);
    }
}

verifyModuleInstallation(path.resolve(__dirname, "../node_modules"));
module.exports = verifyModuleInstallation;
