/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpers";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

export enum ProfilePhotoAlignment {
    LEFT = "left",
    CENTER = "center",
}

/**
 * @varGroup profile
 * @description Variables affecting the profile and edit profile pages.
 */
export const profileVariables = useThemeCache(() => {
    const makeVars = variableFactory("profile");

    /**
     * @varGroup profile.contentBoxes
     * @description Content boxes for the profile page.
     * @expand contentBoxes
     */
    const contentBoxes = makeVars(
        "contentBoxes",
        Variables.contentBoxes({
            depth2: {
                borderType: BorderType.NONE,
            },
            depth3: {
                borderType: BorderType.SEPARATOR,
            },
        }),
    );

    const panelBoxes = makeVars("panelBoxes", Variables.contentBoxes(globalVariables().panelBoxes));

    const photo = makeVars("photo", {
        /**
         * @var profile.photo.border.radius
         * @title Border radius
         * @description Controls the border radius of the photo
         * @type number | string
         */
        border: {
            radius: "50%",
        },
        /**
         * @var profile.photo.size
         * @title Size
         * @description Controls the size of the photo wrapped inside a wrapper
         * @type number
         */
        size: 220,
    });

    return { contentBoxes, panelBoxes, photo };
});
