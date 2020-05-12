/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { EventAttendance } from "@groups/events/events/eventOptions";
import { ButtonTabs } from "@library/forms/buttonTabs/ButtonTabs";
import ButtonTab from "@library/forms/buttonTabs/ButtonTab";
import { t } from "@vanilla/i18n/src";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export default {
    title: "Radio Buttons as Buttons",
    parameters: {
        chromatic: {
            viewports: [1450, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
};

export function RadioButtonsAsButtons(props: { title?: string; accessibleTitle?: string }) {
    const { title = "Standard", accessibleTitle = "Are you going?" } = props;
    return (
        <>
            <StoryHeading depth={1}>{title}</StoryHeading>
            <ButtonTabs accessibleTitle={accessibleTitle} setData={selectedTab => {}}>
                <ButtonTab label={t("Going")} data={EventAttendance.GOING.toString()} />
                <ButtonTab label={t("Maybe")} data={EventAttendance.MAYBE.toString()} />
                <ButtonTab label={t("Not going")} data={EventAttendance.NOT_GOING.toString()} />
            </ButtonTabs>
        </>
    );
}
