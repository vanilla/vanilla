/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { _BookmarkIcon, DropDownMenuIcon } from "@library/icons/common";
import { iconClasses } from "@library/icons/iconStyles";
import { ListItem, ListItemContext } from "@library/lists/ListItem";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import { ListItemMedia } from "@library/lists/ListItemMedia";
import { StoryMetasAll, StoryMetasMinimal } from "@library/metas/Metas.story";
import { StoryContent } from "@library/storybook/StoryContent";
import { STORY_IMAGE, STORY_IPSUM_MEDIUM, STORY_USER } from "@library/storybook/storyData";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import React from "react";

export default {
    title: "Components/Lists",
    includeStories: ["Items"],
};

export function StoryListItems(props: { as?: string; withTitles?: boolean }) {
    const { withTitles } = props;
    const actions = (
        <>
            <Button buttonType={ButtonTypes.ICON_COMPACT} className={iconClasses()._bookmark()}>
                <_BookmarkIcon />
            </Button>
            <Button buttonType={ButtonTypes.ICON_COMPACT}>
                <DropDownMenuIcon />
            </Button>
        </>
    );
    const as = props.as ?? "div";
    return (
        <>
            {withTitles && <StoryHeading>No Images</StoryHeading>}
            <ListItem
                as={as as any}
                url={"#"}
                name={"No image, but everything else"}
                description={STORY_IPSUM_MEDIUM}
                icon={
                    <img
                        src={STORY_USER.photoUrl}
                        alt={STORY_USER.name}
                        style={{ borderRadius: "100%", height: 44, width: 44 }}
                    />
                }
                metas={<StoryMetasAll />}
                actions={actions}
            />
            {withTitles && <StoryHeading>No Images or Actions</StoryHeading>}
            <ListItem
                as={as as any}
                url={"#"}
                name={"No images or actions + excerpt"}
                description={STORY_IPSUM_MEDIUM}
                metas={<StoryMetasAll />}
            />
            {withTitles && <StoryHeading>Minimal</StoryHeading>}
            <ListItem
                as={as as any}
                url={"#"}
                name={"No images or excerpt + options"}
                metas={<StoryMetasMinimal />}
                actions={actions}
            />
            {withTitles && <StoryHeading>With everything</StoryHeading>}
            <ListItem
                as={as as any}
                url={"#"}
                name={"With everything"}
                description={STORY_IPSUM_MEDIUM}
                icon={
                    <img
                        src={STORY_USER.photoUrl}
                        alt={STORY_USER.name}
                        style={{ borderRadius: "100%", height: 44, width: 44 }}
                    />
                }
                metas={<StoryMetasAll />}
                mediaItem={<ListItemMedia src={STORY_IMAGE} alt="Media Item" />}
                actions={actions}
            />
            {withTitles && <StoryHeading>With meta first</StoryHeading>}
            <ListItemContext.Provider value={{ layout: ListItemLayout.TITLE_METAS_DESCRIPTION }}>
                <ListItem
                    as={as as any}
                    url={"#"}
                    name={"With everything"}
                    description={STORY_IPSUM_MEDIUM}
                    icon={
                        <img
                            src={STORY_USER.photoUrl}
                            alt={STORY_USER.name}
                            style={{ borderRadius: "100%", height: 44, width: 44 }}
                        />
                    }
                    metas={<StoryMetasAll />}
                    mediaItem={<ListItemMedia src={STORY_IMAGE} alt="Media Item" />}
                />
            </ListItemContext.Provider>
            {withTitles && <StoryHeading>Long Title</StoryHeading>}
            <ListItem
                as={as as any}
                url={"#"}
                name={
                    "Very long title on this one. It just goes on and on and on. It seems like it never ends. How long can it be?"
                }
                description={STORY_IPSUM_MEDIUM}
                icon={
                    <img
                        src={STORY_USER.photoUrl}
                        alt={STORY_USER.name}
                        style={{ borderRadius: "100%", height: 44, width: 44 }}
                    />
                }
                metas={<StoryMetasAll />}
                mediaItem={<ListItemMedia src={STORY_IMAGE} alt="Media Item" />}
                actions={actions}
            />
        </>
    );
}

export function Items() {
    return (
        <StoryContent>
            <StoryListItems withTitles />
            <StoryHeading>They are responsive without relying on media queries</StoryHeading>
            <StoryParagraph>
                {`We've put an artifically small container here. This mimics what this component might be like if placed
                somewhere like a panel`}
            </StoryParagraph>
            <div style={{ maxWidth: 400, margin: "0 auto" }}>
                <StoryListItems withTitles />
            </div>
        </StoryContent>
    );
}
