/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { MetaIcon, MetaItem, Metas, MetaTag } from "@library/metas/Metas";
import SmartLink from "@library/routing/links/SmartLink";
import { StoryContent } from "@library/storybook/StoryContent";
import React from "react";
import { STORY_DATE } from "@library/storybook/storyData";
import { metasClasses } from "@library/metas/Metas.styles";
import { TagPreset } from "@library/metas/Tags.variables";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { GlobalPreset } from "@library/styles/globalStyleVars";

export default {
    title: "Components/Metas",
    includeStories: ["Items", "TagsAndLabels", "TagsAndLabelsDark"],
};

function SizedBox(props: { title: string; width: number; children: React.ReactNode }) {
    return (
        <div className={css({ marginBottom: 24 })}>
            <h2 className={css({ marginBottom: 8, paddingLeft: 16, paddingRight: 16 })}>{props.title}</h2>
            <div className={css({ maxWidth: props.width, border: "2px solid #e1e1e1", padding: 16 })}>
                {props.children}
            </div>
        </div>
    );
}

export function StoryMetasMinimal() {
    return (
        <>
            <MetaItem>120 Views</MetaItem>
            <MetaItem>4.3k Comments</MetaItem>
            <MetaIcon icon="meta-external" />
            <MetaItem>
                <DateTime timestamp={STORY_DATE}></DateTime>
            </MetaItem>
        </>
    );
}

export function StoryMetasAll() {
    return (
        <>
            <MetaTag tagPreset={TagPreset.GREYSCALE}>Announced</MetaTag>
            <MetaTag tagPreset={TagPreset.GREYSCALE}>Answered</MetaTag>
            <MetaItem>120 Views</MetaItem>
            <MetaItem>4.3k Comments</MetaItem>
            <MetaItem>
                <Translate
                    source="Posted by <0/> in <1/>"
                    c0={
                        <SmartLink to={"#"} className={metasClasses().metaLink}>
                            Mike Jonan
                        </SmartLink>
                    }
                    c1={
                        <SmartLink to={"#"} className={metasClasses().metaLink}>
                            That Category
                        </SmartLink>
                    }
                />
            </MetaItem>
            <MetaItem>
                <DateTime timestamp={STORY_DATE}></DateTime>
            </MetaItem>
        </>
    );
}

export function Items() {
    const allItems = (
        <Metas>
            <StoryMetasAll />
        </Metas>
    );
    const minimalIcons = (
        <Metas>
            <StoryMetasMinimal />
        </Metas>
    );
    return (
        <StoryContent>
            <SizedBox width={300} title={"All items 300px"}>
                {allItems}
            </SizedBox>
            <SizedBox width={600} title={"All items 600px"}>
                {allItems}
            </SizedBox>
            <SizedBox width={300} title={"Minimal items 300px"}>
                {minimalIcons}
            </SizedBox>
            <SizedBox width={600} title={"Minimal items 600px"}>
                {minimalIcons}
            </SizedBox>
        </StoryContent>
    );
}

export function TagsAndLabels() {
    return (
        <StoryContent>
            <SizedBox width={600} title={"All Tag Types"}>
                <Metas>
                    <MetaTag to="#" tagPreset={TagPreset.STANDARD}>
                        Standard
                    </MetaTag>
                    <MetaTag to="#" tagPreset={TagPreset.PRIMARY}>
                        Primary
                    </MetaTag>
                    <MetaTag to="#" tagPreset={TagPreset.GREYSCALE}>
                        Greyscale
                    </MetaTag>
                    <MetaTag to="#" tagPreset={TagPreset.COLORED}>
                        Colored
                    </MetaTag>
                </Metas>
            </SizedBox>
            <SizedBox width={600} title={"All Tag Types (active states)"}>
                <Metas>
                    <MetaTag to="#" className={"focus-visible"} tagPreset={TagPreset.STANDARD}>
                        Standard
                    </MetaTag>
                    <MetaTag to="#" className={"focus-visible"} tagPreset={TagPreset.PRIMARY}>
                        Primary
                    </MetaTag>
                    <MetaTag to="#" className={"focus-visible"} tagPreset={TagPreset.GREYSCALE}>
                        Greyscale
                    </MetaTag>
                    <MetaTag to="#" className={"focus-visible"} tagPreset={TagPreset.COLORED}>
                        Colored
                    </MetaTag>
                </Metas>
            </SizedBox>
        </StoryContent>
    );
}

export const TagsAndLabelsDark = storyWithConfig(
    {
        themeVars: {
            global: {
                options: {
                    preset: GlobalPreset.DARK,
                },
            },
        },
    },
    TagsAndLabels,
);
