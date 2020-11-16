/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryContent } from "@library/storybook/StoryContent";

import React from "react";
import { ActionBar as StoryActionBar } from "@library/headers/ActionBar";
import { MemoryRouter } from "react-router";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { storyWithConfig } from "@library/storybook/StoryContext";

export default {
    title: "Headers",
    parameters: {
        chromatic: {
            viewports: [1400, 400],
        },
    },
};

export const ActionBar = storyWithConfig({ useWrappers: false }, () => (
    <MemoryRouter>
        <StoryContent>
            <StoryHeading> Default </StoryHeading>
            <StoryActionBar />
            <StoryHeading>Full</StoryHeading>
            <StoryActionBar
                callToActionTitle={"Do Action"}
                optionsMenu={
                    <DropDown flyoutType={FlyoutType.LIST}>
                        <DropDownItemButton onClick={() => {}}>someItem</DropDownItemButton>
                    </DropDown>
                }
            />
            <StoryHeading> Loading </StoryHeading>
            <StoryActionBar
                callToActionTitle={"Do Action"}
                optionsMenu={
                    <DropDown flyoutType={FlyoutType.LIST}>
                        <DropDownItemButton onClick={() => {}}>someItem</DropDownItemButton>
                    </DropDown>
                }
                isCallToActionLoading={true}
            />
            <StoryHeading> MobileDropDown </StoryHeading>
            <StoryActionBar
                callToActionTitle={"Do Action"}
                mobileDropDownContent={<div>Hello World</div>}
                mobileDropDownTitle={"Open drop down"}
            />
            <StoryHeading> Extras </StoryHeading>
            <StoryActionBar
                callToActionTitle={"Do Action"}
                optionsMenu={
                    <DropDown flyoutType={FlyoutType.LIST}>
                        <DropDownItemButton onClick={() => {}}>someItem</DropDownItemButton>
                    </DropDown>
                }
                useShadow={false}
                isCallToActionDisabled={true}
            />
        </StoryContent>
    </MemoryRouter>
));
