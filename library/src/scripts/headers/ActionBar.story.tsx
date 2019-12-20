/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import { StoryContent } from "@library/storybook/StoryContent";

import React from "react";
import { ActionBar } from "@library/headers/ActionBar";
import { MemoryRouter } from "react-router";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";

const story = storiesOf("Headers", module);

story.add("ActionBar", () => {
    return (
        <MemoryRouter>
            <StoryContent>
                <StoryHeading> Default </StoryHeading>
                <ActionBar />
                <StoryHeading> Full </StoryHeading>
                <ActionBar
                    callToActionTitle={"Do Action"}
                    optionsMenu={
                        <DropDown flyoutType={FlyoutType.LIST}>
                            <DropDownItemButton onClick={() => {}}>someItem</DropDownItemButton>
                        </DropDown>
                    }
                />
                <StoryHeading> Loading </StoryHeading>
                <ActionBar
                    callToActionTitle={"Do Action"}
                    optionsMenu={
                        <DropDown flyoutType={FlyoutType.LIST}>
                            <DropDownItemButton onClick={() => {}}>someItem</DropDownItemButton>
                        </DropDown>
                    }
                    isCallToActionLoading={true}
                />
                <StoryHeading> MobileDropDown </StoryHeading>
                <ActionBar
                    callToActionTitle={"Do Action"}
                    mobileDropDownContent={<div>Hello World</div>}
                    mobileDropDownTitle={"Open drop down"}
                />
                <StoryHeading> Extras </StoryHeading>
                <ActionBar
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
    );
});
