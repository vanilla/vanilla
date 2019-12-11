/**
 * Set wepback's public path.
 * Otherwise the lookups of dynamically imported webpack files can fail on sites that have a different webroot
 * Eg. installed in a subfolder / using Reverse Proxy.
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { assetUrl, getMeta } from "@library/utility/appUtils";

/**
 * This needs to be a free variable.
 *
 * Webpack does some magic to make this work.
 * It you try and set a local or global version of it, it WILL NOT work.
 *
 * @see https://stackoverflow.com/questions/12934929/what-are-free-variables
 * @see https://webpack.js.org/configuration/output/#output-publicpath
 * @see https://github.com/webpack/webpack/issues/2776#issuecomment-233208623
 */
// @ts-ignore: Cannot find variable warning. See comment aboe.
__webpack_public_path__ = assetUrl("/dist/" + __BUILD__SECTION__ + "/");
