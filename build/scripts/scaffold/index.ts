/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import yargs from "yargs";
import { scaffoldTheme } from "./scaffoldTheme";

yargs
    .scriptName("scaffold")
    .command(
        "theme",
        "Scaffold out a theme",
        yargs => {
            yargs
                .option("rootDirectory", {
                    alias: "d",
                    describe:
                        "Where do you want to create the theme. A symlink will automatically be created into this installation. Typically this would be in directory outside the vanilla installation in a separate git repo.",
                    demandOption: true,
                })
                .option("name", {
                    alias: "n",
                    describe: "The name of the theme. This displays in the dashboard.",
                    default: "New Custom Theme",
                })
                .option("key", {
                    alias: "k",
                    describe: "The key of the theme. This must be unique to the addon.",
                    demandOption: true,
                });
        },
        args => {
            scaffoldTheme({
                directory: args["rootDirectory"] as string,
                themeKey: args["key"] as string,
                themeName: args["name"] as string,
            });
        },
    )
    .demandCommand()
    .help()
    .epilogue(
        "For more information, check out the quickstart guide https://success.vanillaforums.com/kb/articles/168-theming-quickstart",
    ).argv;
