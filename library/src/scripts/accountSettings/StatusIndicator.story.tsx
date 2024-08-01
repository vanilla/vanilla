/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { accountSettingsClasses } from "@library/accountSettings/AccountSettings.classes";
import { StatusIndicator } from "@library/accountSettings/StatusIndicator";
import { ApproveIcon, ErrorIcon } from "@library/icons/common";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { Icon } from "@vanilla/icons";
import React from "react";

export default {
    title: "Account Settings/Status Indicator",
};

export function Default() {
    const classes = accountSettingsClasses();
    return (
        <>
            <StoryHeading depth={1}>Status Indicator</StoryHeading>
            <StoryParagraph>
                A status indicator can be passed any icon and text combination and will display it beside one another by
                default
            </StoryParagraph>
            <div style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
                <StatusIndicator
                    icon={<Icon icon="status-success" className={classes.verified} />}
                    statusText={"Confirmed"}
                />
                <StatusIndicator
                    icon={<Icon icon="status-warning" className={classes.unverified} />}
                    statusText={"Needs Confirmation"}
                />
                <StatusIndicator
                    icon={<Icon icon="status-success" className={classes.verified} />}
                    statusText={"Username Available"}
                />
                <StatusIndicator icon={<ErrorIcon />} statusText={"Username Unavailable"} />
                <StatusIndicator
                    icon={<Icon icon="status-success" className={classes.verified} />}
                    statusText={"Passwords Match"}
                />
            </div>
        </>
    );
}
