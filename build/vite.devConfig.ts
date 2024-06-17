import { defineConfig } from "vite";
import { VANILLA_ROOT } from "./scripts/env";
import { makeViteBuildConfig } from "./vite.makeBuildConfig";
import fse from "fs-extra";
import path from "path";
import EntryModel from "./scripts/utility/EntryModel";

export default defineConfig(() => {
    let buildSections = process.env.BUILD_SECTIONS?.split(",") ?? [];
    if (buildSections.length === 0) {
        buildSections = ["admin", "admin-new", "forum", "layouts", "knowledge"];
    }
    const entryModel = new EntryModel();
    const devEntry = path.join(VANILLA_ROOT, "build", ".vite/dev-build.html");
    entryModel.synthesizeHtmlEntry(devEntry, buildSections);
    const commonConfig = makeViteBuildConfig(devEntry);

    // Dev build
    writeHotReloadConfig();
    process.on("exit", deleteHotReloadConfig);
    process.on("SIGINT", () => process.exit());
    process.on("SIGTERM", () => process.exit());
    process.on("SIGHUP", () => process.exit());
    return {
        ...commonConfig,
        mode: "development",
        optimizeDeps: {
            exclude: [
                "@vanilla/utils",
                "@vanilla/ui",
                "@vanilla/react-utils",
                "@vanilla/dom-utils",
                "@vanilla/icons",
                "@vanilla/i18n",
            ],
            include: [
                "@vanilla/utils > tabbable",
                "@vanilla/ui > @reach/accordion",
                "@vanilla/ui > @reach/combobox",
                "@vanilla/ui > @reach/rect",
            ],
        },
        define: {
            "process.env.NODE_ENV": '"development"',
            "process.env.IS_WEBPACK": true,
        },
    };
});

const hotReloadConfigPath = VANILLA_ROOT + "/conf/hot-build.php";
function writeHotReloadConfig() {
    const contents = `
<?php
$Configuration["HotReload"]["Enabled"] = true;
`;
    fse.writeFileSync(hotReloadConfigPath, contents);
}

function deleteHotReloadConfig() {
    if (fse.existsSync(hotReloadConfigPath)) {
        fse.unlinkSync(hotReloadConfigPath);
    }
}
