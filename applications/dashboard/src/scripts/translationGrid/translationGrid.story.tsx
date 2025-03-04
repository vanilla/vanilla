/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { action } from "@storybook/addon-actions";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { loadLocales, LocaleProvider } from "@vanilla/i18n";
import { TranslationGrid } from "./TranslationGrid";
import { localeData, makeTestTranslationProperty } from "./translationGrid.storyData";

export default {
    title: "Layout",
};
loadLocales(localeData);
const ipsum =
    "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis porta nibh, a egestas tortor lobortis ac. \nPraesent interdum congue nunc, congue volutpat dui maximus commodo. Nunc sagittis libero id ex commodo aliquet. Vivamus venenatis pellentesque lorem, sed molestie justo vehicula eu.";

export const _TranslationGrid = () => {
    return (
        <>
            <LocaleProvider>
                <StoryContent>
                    <StoryHeading depth={1}>Translation Grid</StoryHeading>
                    <StoryParagraph>Border is just to show it works in a scroll container, like a Modal</StoryParagraph>
                </StoryContent>
                <div
                    style={{
                        height: "600px",
                        overflow: "auto",
                        border: "solid #1EA7FD 4px",
                        width: "1028px",
                        maxWidth: "100%",
                        margin: "auto",
                        position: "relative", // Scrolling container must have "position"
                    }}
                >
                    <TranslationGrid
                        sourceLocale="en"
                        properties={[
                            makeTestTranslationProperty("test.1", "Hello world!", false),
                            makeTestTranslationProperty("test.2", ipsum, true),
                            makeTestTranslationProperty("test.3", "Hello world 2!", false),
                            makeTestTranslationProperty("test.4", ipsum, true),
                            makeTestTranslationProperty("test.5", ipsum, true),
                            makeTestTranslationProperty("test.6", "Hello world 6!", false),
                        ]}
                        existingTranslations={{
                            "test.1": "你好，世界",
                            "test.4":
                                "存有悲坐阿梅德，consectetur adipiscing ELIT。前庭sagittis Aliquam NIBH，一个egestas tortor lobortis交流。现在，Planning有时目前，规划周末DUI最大的方便。现在，它是免费的箭头香蕉的优势。 LOREM活消毒的营养，但电视的车辆只是足球",
                        }}
                        inScrollingContainer={true}
                        activeLocale="ca"
                        onActiveLocaleChange={(newLocale) => {
                            action(`Changing active locale`)(newLocale);
                        }}
                        onTranslationUpdate={(field, translation) => {
                            action(`Updating translation value`)(field, translation);
                        }}
                    />
                </div>
            </LocaleProvider>
        </>
    );
};
