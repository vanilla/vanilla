/**
 * Some environmental paths for the build.
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { resolve } from "path";

// Application paths
export const VANILLA_ROOT = resolve(__dirname, "../../");
export const VANILLA_PLUGINS = resolve(VANILLA_ROOT, "plugins");
export const VANILLA_APPS = resolve(VANILLA_ROOT, "applications");
export const VANILLA_THEMES = resolve(VANILLA_ROOT, "themes");

export const DIST_DIRECTORY = resolve(VANILLA_ROOT, "dist");
export const LIBRARY_SRC_DIRECTORY = resolve(VANILLA_ROOT, "library/src/scripts");

// Config files
export const PRETTIER_FILE = resolve(VANILLA_ROOT, "prettier.config.js");
export const TS_CONFIG_FILE = resolve(VANILLA_ROOT, "tsconfig.json");
export const TS_LINT_FILE = resolve(VANILLA_ROOT, "tslint.json");

// Special entries
export const BOOTSTRAP_SOURCE_FILE = resolve(VANILLA_ROOT, "library/src/scripts/bootstrap.ts");
export const POLYFILL_SOURCE_FILE = resolve(VANILLA_ROOT, "build/entries/polyfills.ts");
export const PUBLIC_PATH_SOURCE_FILE = resolve(VANILLA_ROOT, "build/entries/public-path.ts");
