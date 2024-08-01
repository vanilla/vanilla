/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { userContentClasses } from "@library/content/UserContent.styles";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import Container from "@library/layout/components/Container";
import { PanelWidget } from "@library/layout/components/PanelWidget";
import { SubtitleType } from "@library/layout/PageHeadingBox.variables";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import { List } from "@library/lists/List";
import { ListItem } from "@library/lists/ListItem";
import { StoryListItems } from "@library/lists/ListItem.story";
import { MetaItem } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { STORY_IPSUM_LONG } from "@library/storybook/storyData";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpers";
import React from "react";

export default {
    title: "Components/Lists/As Widget",
};

export const WithContainer = storyWithConfig({ useWrappers: false }, () => {
    return (
        <>
            <HomeWidgetContainer
                title={"With Container"}
                options={{
                    maxWidth: 1000,
                    subtitle: {
                        content: "1000px Max Width",
                        type: SubtitleType.OVERLINE,
                    },
                    description: "This is the description of the widget.",
                    viewAll: {
                        to: "#",
                    },
                    isGrid: false,
                }}
            >
                <List options={{ itemBox: { borderType: BorderType.SEPARATOR } }}>
                    <StoryListItems />
                </List>
            </HomeWidgetContainer>
            <HomeWidgetContainer
                title={"This one has a background"}
                options={{
                    outerBackground: {
                        color: "#fafafa",
                    },
                    maxWidth: 1000,
                    subtitle: {
                        content: "1000px Max Width",
                        type: SubtitleType.OVERLINE,
                    },
                    description: "This is the description of the widget.",
                    viewAll: {
                        to: "#",
                    },
                    isGrid: false,
                }}
            >
                <List options={{ itemBox: { borderType: BorderType.SHADOW, background: { color: "#fff" } } }}>
                    <StoryListItems />
                </List>
            </HomeWidgetContainer>
            <HomeWidgetContainer
                title={"Dark widget in a light page"}
                options={{
                    outerBackground: {
                        color: globalVariables().elementaryColors.almostBlack,
                    },
                    maxWidth: 1000,
                    subtitle: {
                        content: "1000px Max Width",
                        type: SubtitleType.OVERLINE,
                    },
                    description: "This is the description of the widget.",
                    viewAll: {
                        to: "#",
                    },
                    isGrid: false,
                }}
            >
                <List options={{ itemBox: { borderType: BorderType.SHADOW, background: { color: "#fff" } } }}>
                    <StoryListItems />
                </List>
            </HomeWidgetContainer>
        </>
    );
});

export const InAPanel = storyWithConfig({ useWrappers: false }, () => {
    return (
        <Container fullGutter>
            <SectionTwoColumns
                mainBottom={
                    <>
                        <PanelWidget>
                            <HomeWidgetContainer
                                title={"List 1 in Panel"}
                                options={{
                                    viewAll: {
                                        to: "#",
                                    },
                                    isGrid: false,
                                }}
                            >
                                <List options={{ itemBox: { borderType: BorderType.SHADOW } }}>
                                    <StoryListItems />
                                </List>
                            </HomeWidgetContainer>
                        </PanelWidget>
                        <PanelWidget>
                            <HomeWidgetContainer
                                title={"List 2 in Panel"}
                                options={{
                                    viewAll: {
                                        to: "#",
                                    },
                                    isGrid: false,
                                }}
                            >
                                <List options={{ itemBox: { borderType: BorderType.SEPARATOR } }}>
                                    <StoryListItems />
                                </List>
                            </HomeWidgetContainer>
                        </PanelWidget>
                    </>
                }
                secondaryBottom={
                    <>
                        <PanelWidget>
                            <div className={userContentClasses().root}>
                                <h4>This is the right panel</h4>
                                <p>{STORY_IPSUM_LONG}</p>
                            </div>
                        </PanelWidget>
                        <PanelWidget>
                            <List options={{ itemBox: { borderType: BorderType.SEPARATOR } }}>
                                <MinimalListItem />
                                <MinimalListItem />
                                <MinimalListItem />
                            </List>
                        </PanelWidget>
                    </>
                }
            />
        </Container>
    );
});

function MinimalListItem() {
    return (
        <ListItem
            url={""}
            name={"This is a list item in the panel."}
            metas={
                <>
                    <MetaItem>Posted: Dec 12 2020</MetaItem>
                    <MetaItem>
                        By{" "}
                        <a href="" className={metasClasses().metaLink}>
                            Adam Charron
                        </a>
                    </MetaItem>
                </>
            }
        />
    );
}
