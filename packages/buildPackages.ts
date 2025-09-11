import react from "@vitejs/plugin-react-swc";
import { print, printError, printSection } from "build/scripts/utility/utils";
import { resolve } from "path";
import dts from "vite-plugin-dts";
import chalk from "chalk";
import fs from "fs-extra";
import bundleGenerator from "dts-bundle-generator";
import path from "path";
import { ChildProcess, exec, spawn, spawnSync } from "child_process";

async function buildPackage(packageName: string, externals: string[] = []) {
    printSection(`Building package ${chalk.yellowBright(packageName)}`);

    const vite = await import("vite");

    const packageDirName = packageName.replace("@vanilla/", "vanilla-");
    const packageRoot = resolve(__dirname, `./${packageDirName}`);
    print("Cleaning /dist directory...");
    fs.removeSync(path.join(packageRoot, "/dist"));

    if (packageName === "@vanilla/icons") {
        prepareIcons();
    }

    // Check if we have a special vite.config.ts file
    const viteConfigPath = resolve(packageRoot, "vite.config.ts");
    let viteConfig: import("vite").InlineConfig;
    if (fs.existsSync(viteConfigPath)) {
        print(`Found custom vite config: ${chalk.yellow(viteConfigPath)}`);
        viteConfig = require(viteConfigPath);
    } else {
        const entryCandidats = [
            resolve(packageRoot, "src/index.ts"),
            resolve(packageRoot, "index.ts"),
            resolve(packageRoot, "src/index.tsx"),
            resolve(packageRoot, "index.tsx"),
        ];

        const entryFile = entryCandidats.find((entry) => fs.existsSync(entry));
        if (!entryFile) {
            throw new Error(`No entry file found for package ${packageName}`);
        }

        viteConfig = vite.defineConfig({
            plugins: [
                react(),
                dts({
                    exclude: ["**/*.stories.tsx", "**/*.spec.ts*", resolve(packageRoot, "dist/**/*")],
                    rollupTypes: true,
                    tsconfigPath: resolve(packageRoot, "./tsconfig.json"),
                    copyDtsFiles: true,
                    root: packageRoot,
                }),
            ],
            root: packageRoot,
            build: {
                emptyOutDir: false,
                outDir: resolve(packageRoot, "dist"),
                minify: false,
                lib: {
                    fileName: "index.mjs",
                    entry: entryFile,
                    formats: ["es"],
                },
                rollupOptions: {
                    external: ["react", "react-dom", "react/jsx-runtime", "lodash-es", ...externals],
                },
            },
        });
    }

    // Switch into that root
    // The dts builder sucks otherwise.
    process.chdir(viteConfig.root ?? packageRoot);

    await vite.build(viteConfig);
}

function prepareIcons() {
    print("Building icon definitions...");
    spawnSync("node", ["./packages/vanilla-icons/generateIconTypes.cjs"], {
        cwd: path.resolve(__dirname, "../"),
        shell: true,
        stdio: "inherit",
    });
}

function buildDtsBundleForUiLibrary() {
    printSection(`Building types for ${chalk.yellowBright("@vanilla/ui-library")}`);

    spawnSync("yarn", ["dts-bundle-generator", "--config", "./packages/vanilla-ui-library/dts-config.cjs"], {
        cwd: path.resolve(__dirname, "../"),
        shell: true,
        stdio: "inherit",
    });
}

function runYarnInstall() {
    printSection(`Running yarn install...`);

    spawnSync("yarn", ["install"], {
        cwd: path.resolve(__dirname, "../"),
        shell: true,
        stdio: "inherit",
    });
}

async function buildVitePackages() {
    await buildPackage("@vanilla/utils");
    await buildPackage("@vanilla/icons", ["@vanilla/utils"]);
    await buildPackage("@vanilla/dom-utils", ["@vanilla/utils"]);
    await buildPackage("@vanilla/react-utils", ["@vanilla/utils", "@vanilla/dom-utils"]);
    await buildPackage("@vanilla/i18n", ["@vanilla/utils", "@vanilla/react-utils"]);
    await buildPackage("@vanilla/ui-library", ["@vanilla/utils", "@vanilla/dom-utils", "@vanilla/react-utils"]);
    buildDtsBundleForUiLibrary();

    runYarnInstall();
}

const initialCwd = process.cwd();

try {
    void buildVitePackages();
} finally {
    process.chdir(initialCwd);
}
