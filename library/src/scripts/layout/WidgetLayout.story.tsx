/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { HomeWidget } from "@library/homeWidget/HomeWidget";
import { STORY_WIDGET_ITEMS } from "@library/homeWidget/HomeWidget.storyItems";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { HomeWidgetItemContentType } from "@library/homeWidget/HomeWidgetItem.styles";
import PanelWidget from "@library/layout/components/PanelWidget";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { widgetLayoutClasses } from "@library/layout/WidgetLayout.styles";
import { List } from "@library/lists/List";
import { ListItem } from "@library/lists/ListItem";
import { StoryMetasMinimal } from "@library/metas/Metas.story";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import breadcrumbsStory from "@library/navigation/breadcrumbs.story";
import { StoryQuickLinks } from "@library/navigation/quicklinks.story";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { STORY_CRUMBS, STORY_IPSUM_MEDIUM } from "@library/storybook/storyData";
import { BorderType } from "@library/styles/styleHelpersBorders";
import React from "react";

export default {
    title: "Layout/Widget Layout",
};

function DummyListItem() {
    return <ListItem as={"li"} url={"#"} name={"Hello Story List Item"} metas={<StoryMetasMinimal />} />;
}

interface IDummy {
    withSubtitle?: boolean;
    withDescription?: boolean;
}

function HeadingBlock(props: IDummy) {
    return (
        <PageHeadingBox
            title="This is a title"
            subtitle={props.withSubtitle ? "Hello subtitle" : undefined}
            description={props.withDescription ? "This is a description for the heading block here" : undefined}
        />
    );
}

function DummyWidget(props: IDummy) {
    return (
        <HomeWidgetContainer>
            <HeadingBlock {...props} />
            <List>
                <DummyListItem />
                <DummyListItem />
                <DummyListItem />
                <DummyListItem />
            </List>
        </HomeWidgetContainer>
    );
}

function DummyWidgetContainer(props: IDummy) {
    const classes = widgetLayoutClasses();
    return (
        <HomeWidgetContainer
            options={{
                outerBackground: {
                    color: "#e1e1e1",
                },
            }}
        >
            <HeadingBlock {...props} />
            <List
                options={{
                    itemBox: {
                        borderType: BorderType.SHADOW,
                    },
                }}
            >
                <DummyListItem />
                <DummyListItem />
                <DummyListItem />
                <DummyListItem />
            </List>
        </HomeWidgetContainer>
    );
}

function DummyPanelWidget() {
    return (
        <WidgetLayout>
            <SectionTwoColumns
                breadcrumbs={<Breadcrumbs>{STORY_CRUMBS}</Breadcrumbs>}
                mainBottom={
                    <PanelWidget>
                        <HomeWidget
                            itemData={STORY_WIDGET_ITEMS.slice(0, 3)}
                            title={"Subcategories"}
                            description={STORY_IPSUM_MEDIUM}
                            itemOptions={{
                                contentType: HomeWidgetItemContentType.TITLE_BACKGROUND,
                                display: { counts: false },
                            }}
                        />
                        <DummyWidget withDescription withSubtitle />
                    </PanelWidget>
                }
                secondaryBottom={
                    <>
                        <PanelWidget>
                            <StoryQuickLinks title="Quick Links" />
                        </PanelWidget>
                        <PanelWidget>
                            <DummyWidget />
                        </PanelWidget>
                    </>
                }
            ></SectionTwoColumns>
        </WidgetLayout>
    );
}

function ComponentPaddingsComp() {
    return (
        <WidgetLayout>
            <DummyWidget withDescription />
            <DummyWidgetContainer />
            <DummyWidget withSubtitle />
            <DummyPanelWidget />
        </WidgetLayout>
    );
}

export const ComponentPaddings = storyWithConfig({ useWrappers: false }, ComponentPaddingsComp);
