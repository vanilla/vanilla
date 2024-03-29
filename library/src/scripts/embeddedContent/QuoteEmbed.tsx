/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { CollapsableContent } from "@library/content/CollapsableContent";
import DateTime from "@library/content/DateTime";
import UserContent from "@library/content/UserContent";
import { UserLabel } from "@library/content/UserLabel";
import { userLabelClasses } from "@library/content/UserLabel.classes";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService.register";
import { quoteEmbedClasses } from "@library/embeddedContent/quoteEmbedStyles";
import { DiscussionIcon, RightChevronIcon } from "@library/icons/common";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { MetaItem, MetaLink, Metas } from "@library/metas/Metas";
import ProfileLink from "@library/navigation/ProfileLink";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@library/utility/appUtils";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import classNames from "classnames";
import React from "react";

interface IProps extends IBaseEmbedProps {
    body: string;
    dateInserted: string;
    insertUser: IUserFragment;
    discussionLink?: string;
    category?: ICategoryFragment;
    // For compatibility, new options are hidden by default
    displayOptions?: {
        showUserLabel?: boolean;
        showCompactUserInfo?: boolean;
        showDiscussionLink?: boolean;
        showPostLink?: boolean;
        showCategoryLink?: boolean;
        expandByDefault?: boolean;
    };
}

/**
 * An embed class for quoted user content on the same site.
 *
 * This is not an editable quote. Instead it an expandable/collapsable snapshot of the quoted/embedded comment/discussion.
 */
export function QuoteEmbed(props: IProps) {
    const { body, insertUser, name, url, dateInserted, discussionLink, category, displayOptions = {} } = props;

    const classes = quoteEmbedClasses();
    const {
        showUserLabel = false,
        showCompactUserInfo = true,
        showDiscussionLink = true,
        showPostLink = false,
        showCategoryLink = false,
        expandByDefault = false,
    } = displayOptions;

    const discussionTitle = t("View Original Discussion");

    const linkToDiscussion = showDiscussionLink && discussionLink && (
        <SmartLink title={discussionTitle} to={discussionLink} className={classes.discussionLink}>
            <DiscussionIcon title={discussionTitle} className={classes.discussionIcon} />
            <ScreenReaderContent>{discussionTitle}</ScreenReaderContent>
        </SmartLink>
    );

    const postTitle = t("View Post");
    const linkToPost = showPostLink && url && (
        <SmartLink to={url} className={classes.postLink}>
            {postTitle}
            <RightChevronIcon title={postTitle} className={classes.postLinkIcon} />
        </SmartLink>
    );

    const showHeader = showUserLabel || name || showCompactUserInfo;

    return (
        <EmbedContainer withPadding={false} className={classes.root}>
            <EmbedContent type="Quote">
                <article className={classes.body}>
                    {showHeader && (
                        <header className={classes.header}>
                            {showUserLabel && (
                                <UserLabel
                                    user={insertUser}
                                    metas={
                                        <>
                                            <MetaLink to={url}>
                                                <DateTime timestamp={dateInserted}></DateTime>
                                            </MetaLink>
                                            {category && <MetaLink to={category.url}>{category.name}</MetaLink>}
                                        </>
                                    }
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
                                <Metas>
                                    <MetaItem className={userLabelClasses().userName}>
                                        <ProfileLink userFragment={insertUser} isUserCard />
                                    </MetaItem>
                                    <MetaLink to={url}>
                                        <DateTime timestamp={dateInserted}></DateTime>
                                    </MetaLink>
                                    {category && <MetaLink to={category.url}>{category.name}</MetaLink>}
                                </Metas>
                            )}
                        </header>
                    )}
                    <CollapsableContent
                        className={classNames(classes.content, { [classes.paddingAdjustment]: showHeader })}
                        isExpandedDefault={!!expandByDefault}
                    >
                        <blockquote className={classes.blockquote} cite={url}>
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
