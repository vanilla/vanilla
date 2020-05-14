/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { ButtonTabs } from "@library/forms/buttonTabs/ButtonTabs";
import ButtonTab from "@library/forms/buttonTabs/ButtonTab";
import { t } from "@vanilla/i18n/src";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { storyWithConfig } from "@library/storybook/StoryContext";

export default {
    title: "Radio Buttons as Buttons",
    parameters: {
        chromatic: {
            viewports: [1450, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
};

export function RadioButtonsAsButtons(props: { title?: string; accessibleTitle?: string; disabled?: boolean }) {
    const { title = "Standard", accessibleTitle = "Are you going?" } = props;
    return (
        <>
            <StoryHeading depth={1}>{title}</StoryHeading>
            <ButtonTabs accessibleTitle={accessibleTitle} setData={selectedTab => {}}>
                <ButtonTab disabled={props.disabled} label={t("Going")} data={"going"} />
                <ButtonTab disabled={props.disabled} label={t("Maybe")} data={"maybe"} />
                <ButtonTab disabled={props.disabled} label={t("Not going")} data={"Not Going"} />
            </ButtonTabs>
        </>
    );
}

export const Disabled = storyWithConfig({}, () => <RadioButtonsAsButtons title="Disabled Buttons" disabled={true} />);
