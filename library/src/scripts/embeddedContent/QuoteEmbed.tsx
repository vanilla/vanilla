/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment, IUserFragmentAndRoles } from "@library/@types/api/users";
import { CollapsableContent } from "@library/content/CollapsableContent";
import UserContent from "@library/content/UserContent";
import { UserLabel } from "@library/content/UserLabel";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { quoteEmbedClasses } from "@library/embeddedContent/quoteEmbedStyles";
import { DiscussionIcon, RightChevronIcon } from "@library/icons/common";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@library/utility/appUtils";
import { ICategoryFragment } from "@vanilla/addon-vanilla/@types/api/categories";
import classNames from "classnames";
import React from "react";

interface IProps extends IBaseEmbedProps {
    body: string;
    dateInserted: string;
    insertUser: IUserFragment | IUserFragmentAndRoles;
    expandByDefault?: boolean;
    discussionLink?: string;
    postLink?: string; // should always be there for citation reference
    category?: ICategoryFragment;
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
    const {
        showUserLabel = false,
        showCompactUserInfo = false,
        showDiscussionLink = false,
        showPostLink = false,
        showCategoryLink = false,
    } = displayOptions;

    const discussionTitle = t("View Original Discussion");

    const linkToDiscussion = showDiscussionLink && discussionLink && (
        <SmartLink title={discussionTitle} to={discussionLink} className={classes.discussionLink}>
            <DiscussionIcon title={discussionTitle} className={classes.discussionIcon} />
            <ScreenReaderContent>{discussionTitle}</ScreenReaderContent>
        </SmartLink>
    );

    const postTitle = t("View Post");
    const linkToPost = showPostLink && postLink && (
        <SmartLink to={postLink} className={classes.postLink}>
            {postTitle}
            <RightChevronIcon title={postTitle} className={classes.postLinkIcon} />
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

                            {name && (
                                <SmartLink
                                    to={url}
                                    className={classNames(classes.titleLink, { [classes.isPadded]: showUserLabel })}
                                >
                                    <h2 className={classes.title}>{name}</h2>
                                </SmartLink>
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
                    <CollapsableContent className={classes.content} isExpandedDefault={!!props.expandByDefault}>
                        <blockquote className={classes.blockquote} cite={postLink}>
                            <UserContent content={body} />
                        </blockquote>
                    </CollapsableContent>
                    {(linkToDiscussion || linkToPost) && (
                        <footer className={classes.footer}>
                            <hr className={classes.footerSeparator} aria-hidden={true} />
                            <div className={classes.footerMain}>
                                {linkToDiscussion}
                                {linkToPost}
                            </div>
                        </footer>
                    )}
                </article>
            </EmbedContent>
        </EmbedContainer>
    );
}
