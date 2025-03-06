/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { UserTitle } from "@library/content/UserTitle";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { MetaItem, Metas } from "@library/metas/Metas";
import ProfileLink from "@library/navigation/ProfileLink";
import React from "react";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import ContentItemClasses from "@vanilla/addon-vanilla/contentItem/ContentItem.classes";
import { useContentItemContext, type IContentItemContext } from "@vanilla/addon-vanilla/contentItem/ContentItemContext";
import { ContentItemPermalink } from "@vanilla/addon-vanilla/contentItem/ContentItemPermalink";
import { ContentItemQuoteButton } from "@vanilla/addon-vanilla/contentItem/ContentItemQuote";

interface IProps {
    user: IUserFragment;
    excludePhoto?: boolean;
    options?: React.ReactNode;
    showOPTag?: boolean;
    isPreview?: boolean;
    isClosed?: boolean;
    categoryID: ICategory["categoryID"];
    readOnly?: boolean;
    additionalAuthorMeta?: React.ReactNode;
    checkBox?: React.ReactNode;
}

export function ContentItemHeader(props: IProps) {
    const { user, excludePhoto, options, readOnly, additionalAuthorMeta } = props;

    const classes = ContentItemClasses();

    const threadItemContext = useContentItemContext();

    const dynamicTitleMetas = ContentItemHeader.additionalMetaItems["author"]
        .filter(({ shouldRender }) => shouldRender(threadItemContext))
        .sort(({ order: order1 }, { order: order2 }) => (order1 ?? 0) - (order2 ?? 0))
        .map(({ component: MetaItemComponent }, index) => <MetaItemComponent key={index} />);

    const authorMeta = (
        <>
            <MetaItem>
                <ProfileLink className={classes.userName} userFragment={user} isUserCard />
            </MetaItem>
            {dynamicTitleMetas}
            <MetaItem>
                <UserTitle user={user} showOPTag={props.showOPTag} />
            </MetaItem>
            {/* same line as author meta, in the end (e.g. insertUser badges) */}
            {additionalAuthorMeta && <MetaItem>{additionalAuthorMeta}</MetaItem>}
        </>
    );

    const dynamicMetadataMetas = ContentItemHeader.additionalMetaItems["metadata"]
        .filter(({ shouldRender }) => shouldRender(threadItemContext))
        .sort(({ order: order1 }, { order: order2 }) => (order1 ?? 0) - (order2 ?? 0))
        .map(({ component: MetaItemComponent }, index) => <MetaItemComponent key={index} />);

    const metadata = (
        <>
            {dynamicMetadataMetas}
            <ContentItemPermalink readOnly={readOnly} />
            {!readOnly && (
                <>
                    <ContentItemQuoteButton
                        scrapeUrl={threadItemContext.recordUrl}
                        categoryID={props.categoryID}
                        isClosed={!!props.isClosed}
                    />
                </>
            )}
            {threadItemContext.extraMetas}
        </>
    );

    if (excludePhoto) {
        return (
            <Metas>
                {/* Render all metas in a single row */}
                {authorMeta}
                {metadata}
            </Metas>
        );
    }

    return (
        <div className={classes.headerRoot} key={user.userID}>
            {props.checkBox}
            <ProfileLink className={classes.userName} userFragment={user} isUserCard aria-hidden="true">
                <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />
            </ProfileLink>
            <div className={classes.headerMain}>
                {/* Render metas on separate rows */}
                <Metas>{authorMeta}</Metas>
                <span className={classes.headerMeta}>{metadata}</span>
            </div>
            {options}
        </div>
    );
}

type ThreadItemHeaderMetaItem = {
    component: React.ComponentType<{}>;
    shouldRender: (context: IContentItemContext) => boolean;
    order?: number;
};

ContentItemHeader.additionalMetaItems = {
    author: [] as ThreadItemHeaderMetaItem[],
    metadata: [] as ThreadItemHeaderMetaItem[],
};

/**
 * Register additional meta item to be rendered in the Thread Item Header.
 *
 * The component will not receive any props.
 *
 * It can access some thread item properties (including `attributes`), through `useThreadItemContext()`.
 */
ContentItemHeader.registerMetaItem = (
    /**
     * The component to render
     *
     */
    component: ThreadItemHeaderMetaItem["component"],

    /**
     * shouldRender receives the thread item context as an argument and should return a boolean.
     */
    shouldRender: (context: IContentItemContext) => boolean,

    options?: {
        /**
         * If placement is `author`, the item will be rendered after the user name, before the title/rank
         *
         * If placement is `metadata`, the item will be rendered before the permalink.
         */
        placement: keyof typeof ContentItemHeader.additionalMetaItems;
        order?: number;
    },
) => {
    const { order, placement = "metadata" } = options ?? {};
    ContentItemHeader.additionalMetaItems[placement].push({
        shouldRender,
        component,
        order,
    });
};
