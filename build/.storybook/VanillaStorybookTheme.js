/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { create } from "@storybook/theming/create";
import vanillaWhite from "../../applications/dashboard/styleguide/public/resources/images/vanilla-white.svg";

export default create({
    base: "dark",
    colorPrimary: "#037DBC",
    colorSecondary: "#037DBC",
    appBorderRadius: 6,
    inputBorderRadius: 6,
    brandTitle: "Vanilla Forums Storybook",
    brandUrl: "https://vanillaforums.com",
    brandImage: vanillaWhite,
});
