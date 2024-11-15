/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ProfileFieldVisibility } from "@dashboard/userProfiles/types/UserProfiles.types";
import { ToolTip } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";
import { globalVariables } from "@library/styles/globalStyleVars";
import { css } from "@emotion/css";
import { t } from "@vanilla/i18n";
import { Mixins } from "@library/styles/Mixins";

export function ProfileFieldVisibilityIcon(props: { visibility: ProfileFieldVisibility }) {
    const { visibility } = props;

    const iconContainerClass = css({
        display: "inline-block",
        marginLeft: 4,
        maxHeight: `calc(1em * ${globalVariables().lineHeights.base})`,
    });

    const iconClasses = css({
        ...Mixins.verticallyAlignInContainer(24, globalVariables().lineHeights.base),
    });

    if (visibility === ProfileFieldVisibility.INTERNAL) {
        return (
            <ToolTip label={t("This information will only be shown to users with permission to view internal info.")}>
                <span className={iconContainerClass}>
                    <Icon className={iconClasses} icon="profile-crown" />
                </span>
            </ToolTip>
        );
    }
    if (visibility === ProfileFieldVisibility.PRIVATE) {
        return (
            <ToolTip label={t("This is private information and will not be shared with other members.")}>
                <span className={iconContainerClass}>
                    <Icon className={iconClasses} icon="profile-lock" />
                </span>
            </ToolTip>
        );
    }
    return <></>;
}
