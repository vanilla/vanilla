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
// import Checkbox from "@library/forms/Checkbox";
import Permission from "@library/features/users/Permission";
import { boolean } from "@storybook/addon-knobs";
import InputBlock from "@library/forms/InputBlock";
import InputTextBlock from "@library/forms/InputTextBlock";
import Paragraph from "@library/layout/Paragraph";
import MultiUserInput from "@library/features/users/MultiUserInput";
import KnowledgeBaseInput from "@knowledge/knowledge-bases/KnowledgeBaseInput";
import StoryExampleAdvancedSearch from "@knowledge/modules/search/components/StoryExampleAdvancedSearch";
import AdvancedSearch from "@knowledge/modules/search/components/AdvancedSearch";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import IndependentSearch from "@library/features/search/IndependentSearch";
import { Devices } from "@library/layout/DeviceContext";
import { splashClasses } from "@library/splash/splashStyles";
import DateRange from "@library/forms/DateRange";
import SearchContext from "@library/contexts/SearchContext";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import { MemoryRouter } from "react-router";
import { PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";

const reactionsStory = storiesOf("FormElements", module);

// Radio as tabs

reactionsStory.add("Radio Buttons as Tabs", () => {
    let activeTab = SearchDomain.ARTICLES;

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

    return (
        <StoryContent>
            <StoryHeading depth={1}>Form Elements</StoryHeading>
            <StoryHeading>Checkbox</StoryHeading>
            {/*<Checkbox label={t("Simple Checkbox")} className="inputBlock" />*/}
            <StoryHeading>InputBlock</StoryHeading>
            <StoryParagraph>Helper component to add label to various inputs</StoryParagraph>
            <InputBlock label={"Cool Label"}>
                <div>{"[Some Cool component]"}</div>
            </InputBlock>
            <StoryHeading>Input Text Block</StoryHeading>
            <InputTextBlock label={t("Text Input")} inputProps={{}} />
            <StoryHeading>Input Text (password type)</StoryHeading>
            <StoryParagraph>You can set the `type` of the text input to any standard HTML5 values.</StoryParagraph>
            <InputTextBlock label={t("Password")} type={"password"} inputProps={{ type: "password" }} />
            <StoryHeading>Radio Buttons styled as tabs</StoryHeading>
            <StoryParagraph>
                The state for this component needs to be managed by the parent. (Will not update here when you click)
            </StoryParagraph>
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
            {/*<StoryHeading>Token Input</StoryHeading>*/}
            {/*<MultiUserInput*/}
            {/*    onChange={doNothing}*/}
            {/*    value={[*/}
            {/*        {*/}
            {/*            value: "Value A",*/}
            {/*            label: "Thing 1",*/}
            {/*            data: {},*/}
            {/*        },*/}
            {/*        {*/}
            {/*            value: "Value B",*/}
            {/*            label: "Thing 2",*/}
            {/*            data: {},*/}
            {/*        },*/}
            {/*        {*/}
            {/*            value: "Value C",*/}
            {/*            label: "Thing 3",*/}
            {/*            data: {},*/}
            {/*        },*/}
            {/*    ]}*/}
            {/*/>*/}
            {/*};*/}

            <StoryHeading>Tokens Input</StoryHeading>
            <MultiUserInput
                onChange={handleUserChange}
                value={[
                    {
                        value: "Astérix",
                        label: "Astérix",
                    },
                    {
                        value: "Obélix",
                        label: "Obélix",
                    },
                    {
                        value: "Idéfix",
                        label: "Idéfix",
                    },
                    {
                        value: "Panoramix",
                        label: "Panoramix",
                    },
                ]}
            />

            <StoryHeading>DropDown with search</StoryHeading>
            {/*<KnowledgeBaseInput*/}
            {/*    className="inputBlock"*/}
            {/*    onChange={doNothing}*/}
            {/*    value={*/}
            {/*        [*/}
            {/*            {*/}
            {/*                label: "Development",*/}
            {/*                value: 4,*/}
            {/*            },*/}
            {/*            {*/}
            {/*                label: "Information Security",*/}
            {/*                value: 7,*/}
            {/*            },*/}
            {/*            {*/}
            {/*                label: "Internal Testing",*/}
            {/*                value: 6,*/}
            {/*            },*/}
            {/*            {*/}
            {/*                label: "Success",*/}
            {/*                value: 5,*/}
            {/*            },*/}
            {/*            {*/}
            {/*                label: "Support",*/}
            {/*                value: 8,*/}
            {/*            },*/}
            {/*        ] as IComboBoxOption[]*/}
            {/*    }*/}
            {/*/>*/}

            <StoryHeading>Independent Search</StoryHeading>

            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <MemoryRouter>
                    {/*<IndependentSearch*/}
                    {/*    buttonClass={splashClasses().searchButton}*/}
                    {/*    buttonBaseClass={ButtonTypes.CUSTOM}*/}
                    {/*    isLarge={true}*/}
                    {/*    placeholder={t("Search")}*/}
                    {/*    inputClass={splashClasses().input}*/}
                    {/*    iconClass={splashClasses().icon}*/}
                    {/*    buttonLoaderClassName={splashClasses().buttonLoader}*/}
                    {/*    contentClass={splashClasses().content}*/}
                    {/*    valueContainerClasses={splashClasses().valueContainer}*/}
                    {/*/>*/}

                    <div className={splashClasses().searchContainer}>
                        <IndependentSearch
                            buttonClass={splashClasses().searchButton}
                            buttonBaseClass={ButtonTypes.CUSTOM}
                            isLarge={true}
                            placeholder={t("Search")}
                            inputClass={splashClasses().input}
                            iconClass={splashClasses().icon}
                            buttonLoaderClassName={splashClasses().buttonLoader}
                            contentClass={splashClasses().content}
                            valueContainerClasses={splashClasses().valueContainer}
                        />
                    </div>
                </MemoryRouter>
            </SearchContext.Provider>

            <StoryHeading>Date Range</StoryHeading>
            <DateRange onStartChange={doNothing} onEndChange={doNothing} start={undefined} end={undefined} />

            <StoryHeading>Radio</StoryHeading>
            {/*<Radio />*/}
        </StoryContent>
    );
});
