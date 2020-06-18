/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { t } from "@vanilla/i18n/src";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { EventAttendance } from "@groups/events/state/eventsTypes";
import { eventsClasses } from "@groups/events/ui/eventStyles";
import RadioInputAsButton from "@library/forms/radioAsButtons/RadioInputAsButton";
import { RadioGroup } from "@library/forms/radioAsButtons/RadioGroup";
import { buttonClasses } from "@library/forms/buttonStyles";

export default {
    title: "Radio Inputs as Buttons",
    parameters: {
        chromatic: {
            viewports: [1450, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
};

export function RadioInputsRenderedAsButtons(props: {
    title?: string;
    accessibleTitle?: string;
    disabled?: boolean;
    buttonClass?: string;
    buttonActiveClass?: string;
}) {
    const { title = "Standard", accessibleTitle = "Are you going?" } = props;
    const [activeItem, setActiveItem] = useState("going");
    const setData = data => {
        setActiveItem(data);
    };

    const classes = eventsClasses();

    return (
        <>
            <StoryHeading depth={1}>{title}</StoryHeading>
            <RadioGroup
                activeItem={activeItem}
                accessibleTitle={accessibleTitle}
                setData={setData}
                className={classes.attendanceSelector}
                buttonActiveClass={buttonClasses().primary}
                buttonClass={buttonClasses().standard}
            >
                <RadioInputAsButton disabled={props.disabled} label={t("Going")} data={EventAttendance.GOING} />
                <RadioInputAsButton disabled={props.disabled} label={t("Maybe")} data={EventAttendance.MAYBE} />
                <RadioInputAsButton
                    disabled={props.disabled}
                    label={t("Not going")}
                    data={EventAttendance.NOT_GOING}
                    className={"isLast"}
                />
            </RadioGroup>
        </>
    );
}

export const LookingLikeTabs = storyWithConfig({}, () => (
    <RadioInputsRenderedAsButtons title="Tab Style Buttons" buttonClass={""} buttonActiveClass={""} />
));

export const Disabled = storyWithConfig({}, () => (
    <RadioInputsRenderedAsButtons title="Disabled Buttons" disabled={true} />
));
