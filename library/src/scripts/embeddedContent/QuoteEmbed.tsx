/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useCallback, useMemo, useEffect } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { IUser, IUserFragment, IUserFragmentAndRoles } from "@library/@types/api/users";
import { useUniqueID } from "@library/utility/idUtils";
import classnames from "classnames";
import { makeProfileUrl, t } from "@library/utility/appUtils";
import SmartLink from "@library/routing/links/SmartLink";
import DateTime from "@library/content/DateTime";
import { CollapsableContent } from "@library/content/CollapsableContent";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { BottomChevronIcon, DiscussionIcon, RightChevronIcon, TopChevronIcon } from "@library/icons/common";
import UserContent from "@library/content/UserContent";
import { quoteEmbedClasses } from "@library/embeddedContent/quoteEmbedStyles";
import { metasClasses } from "@library/styles/metasStyles";
import classNames from "classnames";
import { ICategory, ICategoryFragment } from "@vanilla/addon-vanilla/@types/api/categories";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { UserLabel } from "@library/content/UserLabel";

interface IProps extends IBaseEmbedProps {
    body: string;
    dateInserted: string;
    insertUser: IUserFragment | IUserFragmentAndRoles;
    expandByDefault?: boolean;
    discussionLink?: string;
    postLink?: string; // should always be there for citation reference
    category: ICategoryFragment;
    // For compatibility, new options are hidden by default
    displayOptions?: {
        showUserLabel?: boolean;
        showCompactUserInfo?: boolean;
        showDiscussionLink?: boolean;
        showPostLink?: boolean;
        showCategoryLink?: boolean;
    };
}

/**
 * An embed class for quoted user content on the same site.
 *
 * This is not an editable quote. Instead it an expandable/collapsable snapshot of the quoted/embedded comment/discussion.
 */
export function QuoteEmbed(props: IProps) {
    const {
        body,
        insertUser,
        name,
        url,
        dateInserted,
        discussionLink,
        postLink,
        category,
        displayOptions = {},
    } = props;

    const classes = quoteEmbedClasses();
    const userUrl = makeProfileUrl(insertUser.name);
    const classesMeta = metasClasses();
    const {
        showUserLabel = false,
        showCompactUserInfo = false,
        showDiscussionLink = false,
        showPostLink = false,
        showCategoryLink = false,
    } = displayOptions;

    const discussionTitle = t("View Original Discussion");

    const linkToDiscussion = showDiscussionLink && discussionLink && (
        <SmartLink title={discussionTitle} to={discussionLink}>
            <DiscussionIcon title={discussionTitle} />
            <ScreenReaderContent>{discussionTitle}</ScreenReaderContent>
        </SmartLink>
    );

    const postTitle = t("View Post");
    const linkToPost = showPostLink && postLink && (
        <SmartLink to={postLink}>
            {postTitle}
            <RightChevronIcon title={postTitle} />
        </SmartLink>
    );

    return (
        <EmbedContainer withPadding={false} className={classes.root}>
            <EmbedContent type="Quote" inEditor={props.inEditor}>
                <article className={classes.body}>
                    {(showUserLabel || name) && (
                        <header className={classes.header}>
                            {showUserLabel && (
                                <UserLabel
                                    user={insertUser}
                                    date={dateInserted}
                                    dateLink={postLink}
                                    category={category}
                                    displayOptions={{
                                        showCategory: showCategoryLink,
                                        showRole: true,
                                    }}
                                />
                            )}

                            {/*<SmartLink to={userUrl} className={classNames(classesMeta.meta, classes.userName)}>*/}
                            {/*    <span className="embedQuote-userName">{insertUser.name}</span>*/}
                            {/*</SmartLink>*/}

                            {/*<div className={classesMeta.root}>*/}
                            {/*    <SmartLink to={url} className={classNames(classesMeta.meta)}>*/}
                            {/*        <DateTime timestamp={dateInserted} />*/}
                            {/*    </SmartLink>*/}
                            {/*    {category && showCategoryLink && (*/}
                            {/*        <SmartLink to={category.url} className={classNames(classesMeta.meta)}>*/}
                            {/*            {category.name}*/}
                            {/*        </SmartLink>*/}
                            {/*    )}*/}
                            {/*</div>*/}

                            {name && (
                                <h2 className={classes.title}>
                                    <SmartLink to={url} className={classes.titleLink}>
                                        {name}
                                    </SmartLink>
                                </h2>
                            )}

                            {!showUserLabel && showCompactUserInfo && (
                                <UserLabel
                                    user={insertUser}
                                    date={dateInserted}
                                    dateLink={postLink}
                                    compact={true}
                                    category={category}
                                    displayOptions={{ showCategory: showCategoryLink }}
                                />
                            )}
                        </header>
                    )}
                    <CollapsableContent
                        className={classes.content}
                        maxHeight={200}
                        isExpandedDefault={!!props.expandByDefault}
                    >
                        <blockquote cite={postLink}>
                            <UserContent content={body} />
                        </blockquote>
                    </CollapsableContent>
                    {(linkToDiscussion || linkToPost) && (
                        <footer className={classes.footer}>
                            {linkToDiscussion}
                            {linkToPost}
                        </footer>
                    )}
                </article>
            </EmbedContent>
        </EmbedContainer>
    );
}
