/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

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

    /**
     * @varGroup profile.badges
     * @commonTitle Profile Badges
     */
    const badges = makeVars("badges", {
        size: {
            /**
             * @var profile.badges.size.width
             * @title Controls the width of the badge items
             * @type string | number
             */
            width: 100,
        },
        /**
         * @var profile.badges.alignment
         * @title Controls the alignment of the badge and count items
         * @type string
         * @enum left | center
         */
        alignment: ProfilePhotoAlignment.LEFT,
    });

    const photo = makeVars("photo", {
        /**
         * @var profile.photo.border.radius
         * @title Controls the border radius of the photo
         * @type number
         */
        border: {
            radius: "50%",
        },
        /**
         * @var profile.photo.size
         * @title Controls the size of the photo wrapped inside a wrapper
         * @type  number
         */
        size: 220,
    });

    return { contentBoxes, badges, photo };
});
