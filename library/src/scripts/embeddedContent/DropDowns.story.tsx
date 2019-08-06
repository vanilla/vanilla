/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { SearchDomain } from "@knowledge/modules/search/SearchPageModel";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import DropDown from "@library/flyouts/DropDown";
import { t } from "@library/utility/appUtils";
import InsertUpdateMetas from "@library/result/InsertUpdateMetas";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import MeBoxDropDownItemList from "@library/headers/mebox/pieces/MeBoxDropDownItemList";
import { MeBoxItemType } from "@library/headers/mebox/pieces/MeBoxDropDownItem";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { DeviceProvider, Devices, useDevice } from "@library/layout/DeviceContext";
import StoryExampleDropDownSearch from "@library/embeddedContent/StoryExampleDropDownSearch";
import { StoryExampleDropDown } from "./StoryExampleDropDown";
// import "react-day-picker/lib/style.css";

const reactionsStory = storiesOf("Dropdowns", module);

// Radio as tabs

const doNothing = () => {};

reactionsStory.add("Dropdowns", () => {
    let activeTab = SearchDomain.ARTICLES;
    const classesInputBlock = inputBlockClasses();

    const doNothing = () => {
        return;
    };

    /**
     * Simple form setter.
     */
    const handleUserChange = (options: IComboBoxOption[]) => {
        // Do something
        doNothing();
    };

    //<DeviceProvider>
    // const device = useDevice();
    // </DeviceProvider>

    return (
        <StoryContent>
            <StoryHeading depth={1}>Drop Down</StoryHeading>
            <StoryParagraph>
                Note that these dropdowns can easily be transformed into modals on mobile by using the
                &quot;openAsModal&quot; property.
            </StoryParagraph>
            <StoryTiles>
                <StoryTileAndTextCompact>
                    <StoryExampleDropDown />
                </StoryTileAndTextCompact>
            </StoryTiles>
        </StoryContent>
    );
});
