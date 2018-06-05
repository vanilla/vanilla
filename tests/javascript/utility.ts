/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Utility for importing everything from a wepback require.context
 * https://webpack.js.org/guides/dependency-management/#context-module-api
 */
export function importAll(r: any) {
    r.keys().forEach(r);
}
