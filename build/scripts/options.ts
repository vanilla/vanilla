import yargs from "yargs";
import { getVanillaConfig } from "./utility/configUtils";

yargs
    .option("verbose", {
        alias: "v",
        default: false,
    })
    .options("mode", {
        default: BuildMode.PRODUCTION,
    })
    .options("config", {
        default: "config.php",
    })
    .options("fix", {
        alias: "f",
        default: false,
    })
    .options("install", {
        alias: "i",
        default: false,
    });

export const enum BuildMode {
    DEVELOPMENT = "development",
    PRODUCTION = "production",
    ANALYZE = "analyze",
    POLYFILLS = "polyfills",
}

export interface IBuildOptions {
    mode: BuildMode;
    verbose: boolean;
    fix: boolean;
    install: boolean;
    enabledAddonKeys: string[];
    configFile: string;
    phpConfig: any;
}

/**
 * Parse enabled addons keys out a vanilla config.
 */
function parseEnabledAddons(config: any) {
    const addonKeys: string[] = [];
    for (const [key, value] of Object.entries(config.EnabledApplications)) {
        if (value) {
            addonKeys.push(key);
        }
    }

    for (const [key, value] of Object.entries(config.EnabledPlugins)) {
        if (value) {
            addonKeys.push(key);
        }
    }

    return addonKeys;
}

export async function getOptions(): Promise<IBuildOptions> {
    const config = await getVanillaConfig(yargs.argv.config);
    return {
        mode: yargs.argv.mode,
        verbose: yargs.argv.verbose,
        enabledAddonKeys: parseEnabledAddons(config),
        configFile: yargs.argv.config,
        fix: yargs.argv.fix,
        phpConfig: config,
        install: yargs.argv.install,
    };
}
