/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryTiles } from "@library/storybook/StoryTiles";
import Button from "@library/forms/Button";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import { buttonClasses, ButtonTypes, buttonUtilityClasses } from "@library/forms/buttonStyles";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryTile } from "@library/storybook/StoryTile";
import { globalVariables } from "@library/styles/globalStyleVars";
import { unit } from "@library/styles/styleHelpers";
import { CheckCompactIcon, CloseCompactIcon } from "@library/icons/common";
import { ComposeIcon } from "@library/icons/titleBar";
import RadioTabs from "@library/forms/radioTabs/RadioTabs";
import { t } from "@library/utility/appUtils";
import { SearchDomain } from "@knowledge/modules/search/SearchPageModel";
import RadioTab from "@library/forms/radioTabs/RadioTab";
import Checkbox from "@library/forms/Checkbox";
import Permission from "@library/features/users/Permission";
import { boolean } from "@storybook/addon-knobs";
import InputBlock from "@library/forms/InputBlock";
import InputTextBlock from "@library/forms/InputTextBlock";
import Paragraph from "@library/layout/Paragraph";
import MultiUserInput from "@library/features/users/MultiUserInput";

const reactionsStory = storiesOf("FormElements", module);

// Radio as tabs

reactionsStory.add("Radio Buttons as Tabs", () => {
    let activeTab = SearchDomain.ARTICLES;

    const doNothing = () => {
        return;
    };

    return (
        <StoryContent>
            <StoryHeading depth={1}>Form Elements</StoryHeading>
            <StoryHeading>Checkbox</StoryHeading>
            <Checkbox label={t("Simple Checkbox")} className="inputBlock" />

            <StoryHeading>InputBlock</StoryHeading>
            <StoryParagraph>Helper component to add label to various inputs</StoryParagraph>
            <InputBlock label={"[My Label]"}>{"[My Input]"}</InputBlock>

            <StoryHeading>Input Text Block</StoryHeading>
            <InputTextBlock label={t("Text Input")} inputProps={{}} />

            <StoryHeading>Input Text (password type)</StoryHeading>
            <StoryParagraph>You can set the `type` of the text input to any standard HTML5 values.</StoryParagraph>
            <InputTextBlock label={t("Password")} type={"password"} inputProps={{ type: "password" }} />

            <StoryHeading>Radio Buttons styled as tabs</StoryHeading>
            <StoryParagraph>
                The state for this component needs to be managed by the parent. (Will not update here when you click)
            </StoryParagraph>

            <StoryHeading>Token Input</StoryHeading>
            <MultiUserInput
                onChange={doNothing}
                value={[
                    {
                        value: "Value A",
                        label: "Thing 1",
                        data: {},
                    },
                    {
                        value: "Value B",
                        label: "Thing 2",
                        data: {},
                    },
                    {
                        value: "Value C",
                        label: "Thing 3",
                        data: {},
                    },
                ]}
            />

            <RadioTabs
                accessibleTitle={t("Search in:")}
                prefix="advancedSearchDomain"
                setData={doNothing}
                activeTab={activeTab}
                childClass="advancedSearchDomain-tab"
            >
                <RadioTab label={t("Articles")} position="left" data={SearchDomain.ARTICLES} />
                <RadioTab label={t("Everywhere")} position="right" data={SearchDomain.EVERYWHERE} />
            </RadioTabs>
        </StoryContent>
    );
});
