/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { IEvent } from "@library/events/Event";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { EventAttendance } from "@library/events/eventOptions";
import { EventList as EventListComponent } from "@library/events/EventList";
import { ButtonTabs } from "@library/forms/buttonTabs/ButtonTabs";
import { ButtonTab } from "@library/forms/buttonTabs/ButtonTab";
import { t } from "@vanilla/i18n/src";

export default {
    title: "Radio Buttons as Buttons",
    parameters: {},
};

export function RadioButtonsAsButtons(props: { title?: string; accessibleTitle?: string }) {
    const { title = "Standard", accessibleTitle = "Are you going?" } = props;
    return (
        <>
            <StoryHeading depth={1}>{title}</StoryHeading>
            <ButtonTabs accessibleTitle={accessibleTitle} setData={({}) => {}}>
                <ButtonTab label={t("Going")} data={EventAttendance.GOING.toString()} />
                <ButtonTab label={t("Maybe")} data={EventAttendance.MAYBE.toString()} />
                <ButtonTab label={t("Not going")} data={EventAttendance.NOT_GOING.toString()} />
            </ButtonTabs>
        </>
    );
}
