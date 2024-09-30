/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { threadItemHeaderClasses } from "@vanilla/addon-vanilla/thread/ThreadItemHeader.classes";
import { UserTitle } from "@library/content/UserTitle";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { MetaItem, Metas } from "@library/metas/Metas";
import ProfileLink from "@library/navigation/ProfileLink";
import React from "react";
import ThreadItemPermalink from "@vanilla/addon-vanilla/thread/ThreadItemPermalink";
import { IThreadItemContext, useThreadItemContext } from "@vanilla/addon-vanilla/thread/ThreadItemContext";

interface IProps {
    user: IUserFragment;
    excludePhoto?: boolean;
    options?: React.ReactNode;
    showOPTag?: boolean;
    isPreview?: boolean;
}

export function ThreadItemHeader(props: IProps) {
    const { user, excludePhoto, options } = props;

    const classes = threadItemHeaderClasses();

    const threadItemContext = useThreadItemContext();

    const dynamicTitleMetas = ThreadItemHeader.additionalMetaItems["author"]
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
        </>
    );

    const dynamicMetadataMetas = ThreadItemHeader.additionalMetaItems["metadata"]
        .filter(({ shouldRender }) => shouldRender(threadItemContext))
        .sort(({ order: order1 }, { order: order2 }) => (order1 ?? 0) - (order2 ?? 0))
        .map(({ component: MetaItemComponent }, index) => <MetaItemComponent key={index} />);

    const metadata = (
        <>
            {dynamicMetadataMetas}
            <ThreadItemPermalink />
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
        <div className={classes.root}>
            <ProfileLink className={classes.userName} userFragment={user} isUserCard aria-hidden="true">
                <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />
            </ProfileLink>
            <div className={classes.main}>
                {/* Render metas on separate rows */}
                <Metas>{authorMeta}</Metas>
                <Metas>{metadata}</Metas>
            </div>
            {options}
        </div>
    );
}

type ThreadItemHeaderMetaItem = {
    component: React.ComponentType<{}>;
    shouldRender: (context: IThreadItemContext) => boolean;
    order?: number;
};

ThreadItemHeader.additionalMetaItems = {
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
ThreadItemHeader.registerMetaItem = (
    /**
     * The component to render
     *
     */
    component: ThreadItemHeaderMetaItem["component"],

    /**
     * shouldRender receives the thread item context as an argument and should return a boolean.
     */
    shouldRender: (context: IThreadItemContext) => boolean,

    options?: {
        /**
         * If placement is `author`, the item will be rendered after the user name, before the title/rank
         *
         * If placement is `metadata`, the item will be rendered before the permalink.
         */
        placement: keyof typeof ThreadItemHeader.additionalMetaItems;
        order?: number;
    },
) => {
    const { order, placement = "metadata" } = options ?? {};
    ThreadItemHeader.additionalMetaItems[placement].push({
        shouldRender,
        component,
        order,
    });
};
