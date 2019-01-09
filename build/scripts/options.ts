import yargs from "yargs";
import { getVanillaConfig } from "./utility/configUtils";

export enum BuildMode {
    DEVELOPMENT = "development",
    PRODUCTION = "production",
    ANALYZE = "analyze",
    TEST = "test",
    TEST_WATCH = "testwatch",
    TEST_DEBUG = "testdebug",
}

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
    .options("low-memory", {
        default: false,
    })
    .options("install", {
        alias: "i",
        default: false,
    });

export interface IBuildOptions {
    mode: BuildMode;
    verbose: boolean;
    fix: boolean;
    install: boolean;
    lowMemory: boolean;
    enabledAddonKeys: string[];
    configFile: string;
    phpConfig: any;
    devIp: string;
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
    // We only want/need to parse the config for development builds to see which addons are enabled.
    // CI does not have a config file so don't look one up if we are
    let config: any = {};
    let enabledAddonKeys: string[] = [];
    let devIp = "localhost";
    if (yargs.argv.mode === BuildMode.DEVELOPMENT) {
        config = await getVanillaConfig(yargs.argv.config);
        devIp = config.HotReload && config.HotReload.IP ? config.HotReload.IP : "localhost";
        enabledAddonKeys = parseEnabledAddons(config);
    }

    return {
        mode: yargs.argv.mode,
        verbose: yargs.argv.verbose,
        enabledAddonKeys,
        lowMemory: yargs.argv["low-memory"],
        configFile: yargs.argv.config,
        fix: yargs.argv.fix,
        phpConfig: config,
        install: yargs.argv.install,
        devIp,
    };
}
