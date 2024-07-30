/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { STORY_IPSUM_LONG, STORY_IPSUM_MEDIUM, STORY_IPSUM_SHORT } from "@library/storybook/storyData";
import { SuggestedAnswers } from "@library/suggestedAnswers/SuggestedAnswers";
import { SuggestedAnswersProvider } from "@library/suggestedAnswers/SuggestedAnswers.context";
import { ISuggestedAnswer } from "@library/suggestedAnswers/SuggestedAnswers.variables";
import { setMeta } from "@library/utility/appUtils";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Widgets/SuggestedAnswers",
};

export const LightBackground = storyWithConfig({}, () => {
    return <MockStory title="Default Theme Variables" />;
});

export const DarkBackground = storyWithConfig(
    {
        themeVars: {
            global: {
                mainColors: {
                    bg: "#303030",
                    fg: "#efefef",
                    primary: "#ff00ff",
                },
            },
        },
    },
    () => {
        return <MockStory title="Dark Background Theme" />;
    },
);

export const CustomTheme = storyWithConfig(
    {
        themeVars: {
            global: {
                mainColors: {
                    primary: "#ff0",
                },
            },
            suggestedAnswers: {
                box: {
                    borderType: "none",
                    background: {
                        color: "#0033cc",
                    },
                },
                font: {
                    color: "#0f0",
                },
                item: {
                    box: {
                        border: {
                            radius: 10,
                        },
                        background: {
                            color: "#6309cf",
                        },
                    },
                    font: {
                        color: "#fff",
                    },
                    title: {
                        color: "#0ff",
                        weight: "normal",
                    },
                },
            },
        },
    },
    () => {
        return <MockStory title="Custom Theme Variables" />;
    },
);

const suggestions: ISuggestedAnswer[] = [
    {
        aiSuggestionID: 1,
        format: "Vanilla",
        type: "discussion",
        url: "#",
        documentID: 1,
        title: "Suggested Answer from a Vanilla Discussion",
        summary: STORY_IPSUM_LONG,
        hidden: false,
        sourceIcon: "new-discussion",
    },
    {
        aiSuggestionID: 2,
        format: "Vanilla",
        type: "article",
        url: "#",
        documentID: 2,
        title: "Suggested Answer from a Vanilla KB Article",
        summary: STORY_IPSUM_SHORT,
        hidden: false,
        sourceIcon: "data-article",
    },
    {
        aiSuggestionID: 3,
        format: "Zendesk",
        type: "article",
        url: "#",
        documentID: 3,
        title: "Suggested Answer from a Zendesk KB Article",
        summary: STORY_IPSUM_MEDIUM,
        hidden: false,
        sourceIcon: "logo-zendesk",
    },
];

const queryClient = new QueryClient();

function MockStory(props: { title: string }) {
    setMeta("aiAssistant", { name: "AI Assistant" });

    return (
        <QueryClientProvider client={queryClient}>
            <StoryHeading>{props.title}</StoryHeading>
            <SuggestedAnswersProvider value={{ discussionID: 1 }}>
                <SuggestedAnswers suggestions={suggestions} showSuggestions={true} />
            </SuggestedAnswersProvider>
        </QueryClientProvider>
    );
}
