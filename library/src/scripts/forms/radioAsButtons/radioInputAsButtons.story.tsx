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
import RadioInputAsButton, { IRadioInputAsButtonClasses } from "@library/forms/radioAsButtons/RadioInputAsButton";
import { RadioGroup } from "@library/forms/radioAsButtons/RadioGroup";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { radioInputAsTabClasses } from "@library/forms/radioAsButtons/radioInputAsTab.styles";
import { radioInputAsButtonsClasses } from "@library/forms/radioAsButtons/radioInputAsButtons.styles";

export default {
    title: "Components/Radio Input",
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
    message?: string;
    classes?: IRadioInputAsButtonClasses;
}) {
    const {
        title = "Standard",
        accessibleTitle = "Are you going?",
        message = false,
        classes = radioInputAsButtonsClasses(),
    } = props;
    const [activeItem, setActiveItem] = useState("going");
    const setData = (data) => {
        setActiveItem(data);
    };

    return (
        <>
            <StoryHeading depth={1}>{title}</StoryHeading>
            {message && <StoryParagraph>{message}</StoryParagraph>}
            <RadioGroup
                activeItem={activeItem}
                accessibleTitle={accessibleTitle}
                setData={setData}
                classes={classes}
                buttonClass={props.buttonClass}
                buttonActiveClass={props.buttonActiveClass}
            >
                <RadioInputAsButton disabled={props.disabled} label={t("Going")} data={"going"} />
                <RadioInputAsButton disabled={props.disabled} label={t("Maybe")} data={"maybe"} />
                <RadioInputAsButton
                    disabled={props.disabled}
                    label={t("Not going")}
                    data={"not going"}
                    className={"isLast"}
                />
            </RadioGroup>
        </>
    );
}

export const LookingLikeTabs = storyWithConfig({}, () => (
    <RadioInputsRenderedAsButtons
        title="Tab Style Buttons"
        buttonClass={""}
        buttonActiveClass={""}
        classes={radioInputAsTabClasses()}
        message={
            "Please note that these only visually look like our tabs. They are NOT accessible or semantically tabs."
        }
    />
));

export const Disabled = storyWithConfig({}, () => (
    <RadioInputsRenderedAsButtons title="Disabled Buttons" disabled={true} />
));
