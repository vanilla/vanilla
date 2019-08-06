/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useMemo } from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import RadioTabs from "@library/forms/radioTabs/RadioTabs";
import { t } from "@library/utility/appUtils";
import { SearchDomain } from "@knowledge/modules/search/SearchPageModel";
import RadioTab from "@library/forms/radioTabs/RadioTab";
import InputBlock from "@library/forms/InputBlock";
import InputTextBlock from "@library/forms/InputTextBlock";
import MultiUserInput from "@library/features/users/MultiUserInput";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import IndependentSearch from "@library/features/search/IndependentSearch";
import { splashClasses } from "@library/splash/splashStyles";
import DateRange from "@library/forms/DateRange";
import SearchContext from "@library/contexts/SearchContext";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import { MemoryRouter } from "react-router";
import Checkbox from "@library/forms/Checkbox";
import StoryExampleDropDownSearch from "@library/embeddedContent/StoryExampleDropDownSearch";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import RadioButton from "@library/forms/RadioButton";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import Result from "@library/result/Result";
import ResultList from "@library/result/ResultList";
import { ResultMeta } from "@library/result/ResultMeta";
// import "react-day-picker/lib/style.css";

const story = storiesOf("Search", module);

// Radio as tabs

const doNothing = () => {};

story.add("Search Elements", () => {
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

    // To avoid clashing with other components also using these radio buttons, you need to generate a unique ID for the group.

    const radioButtonGroup1 = uniqueIDFromPrefix("radioButtonGroupA");
    const radioButtonGroup2 = uniqueIDFromPrefix("radioButtonGroupB");

    return (
        <StoryContent>
            <StoryHeading depth={1}>Search Elements</StoryHeading>

            <StoryHeading>Search Box</StoryHeading>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <MemoryRouter>
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

            <StoryHeading>Search Result</StoryHeading>
            <Result
                location={[{}]}
                meta={[
                    <ResultMeta
                        updateUser={{
                            userID: 1,
                        }}
                        dateUpdated={"2016-07-25 17:51:15"}
                    />,
                ]}
                name={"Example search result"}
                url={"#"}
            />

            <StoryHeading>Search Result - No meta</StoryHeading>
            <StoryHeading>Search Result - No Excerpt</StoryHeading>
            <StoryHeading>Search Result - No Excerpt, no meta</StoryHeading>
            <StoryHeading>Draft (same results component)</StoryHeading>
            <StoryHeading>Category (same results component)</StoryHeading>
        </StoryContent>
    );
});
