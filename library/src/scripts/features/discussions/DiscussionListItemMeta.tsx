/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import {
    IDiscussionItemOptions,
    discussionListVariables,
} from "@library/features/discussions/DiscussionList.variables";
import { useCurrentUserSignedIn } from "@library/features/users/userHooks";
import { MetaIcon, MetaItem, MetaLink, MetaTag } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import ProfileLink from "@library/navigation/ProfileLink";
import { t } from "@vanilla/i18n";
import React from "react";
import qs from "qs";
import { PermissionMode } from "@library/features/users/Permission";
import DateTime from "@library/content/DateTime";
import { metasClasses } from "@library/metas/Metas.styles";
import { slugify } from "@vanilla/utils";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { qnaStatus } from "./DiscussionListItem";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { getMeta } from "@library/utility/appUtils";
import { sprintf } from "sprintf-js";

export interface IDiscussionItemMetaProps extends IDiscussion {
    inTile?: boolean;
    discussionOptions?: IDiscussionItemOptions;
}

export function DiscussionListItemMeta(props: IDiscussionItemMetaProps) {
    const { hasPermission } = usePermissionsContext();
    const {
        item: {
            metas: { display, asIcons: renderAsIcons },
        },
    } = discussionListVariables(props.discussionOptions);

    const classes = discussionListClasses(props.discussionOptions, props.inTile);
    const variables = discussionListVariables(props.discussionOptions);

    const {
        pinned,
        closed,
        insertUser,
        countViews,
        countComments,
        category,
        dateLastComment,
        lastUser,
        unread,
        countUnread,
        attributes,
        tags,
        score,
        resolved,
        inTile,
    } = props;

    const currentUserSignedIn = useCurrentUserSignedIn();

    const displayUnreadCount =
        currentUserSignedIn &&
        !(props.type == "redirect" && props.closed) &&
        ((unread && !inTile) || (countUnread !== undefined && countUnread > 0 && display.unreadCount));

    const displayCategory = !!category && display.category;

    const displayStartedByUser = !!insertUser && display.startedByUser;
    // By default "lastUser" is "insertUser", we don't want ot display it twice if no-one has commented.
    const displayLastUser = countComments > 0 && !!lastUser && display.lastUser;

    const displayQnaStatus = !!attributes?.question?.status && display.qnaStatus;
    const getQNAClass = (status: string) => {
        switch (status) {
            case "unanswered":
                return classes.qnaStatusUnanswered;
            case "answered":
                return classes.qnaStatusAnswered;
            case "accepted":
                return classes.qnaStatusAccepted;
            default:
                return "";
        }
    };

    const displayViewCount = countViews > 0 && display.viewCount;
    const renderViewCountAsIcon = displayViewCount && renderAsIcons;

    const displayCommentCount = countComments > 0 && display.commentCount;
    const renderCommentCountAsIcon = displayCommentCount && renderAsIcons;

    const displayScore = display.score;
    const renderScoreAsIcon = displayScore && renderAsIcons;

    const displayLastCommentDate = !!dateLastComment && display.lastCommentDate;
    const renderLastCommentDateAsIcon = displayLastCommentDate && renderAsIcons;

    const shouldShowUserTags: boolean =
        !!tags && tags.length > 0 && display.userTags && variables.userTags.maxNumber > 0;

    const canResolve = hasPermission("staff.allow", { mode: PermissionMode.GLOBAL_OR_RESOURCE });
    const displayResolved = resolved !== undefined && canResolve && display.resolved;

    const customLayoutsForDiscussionListIsEnabled = getMeta("featureFlags.customLayout.discussionList.Enabled", false);

    const tagsAndResolvedMetas = (
        <>
            {displayResolved && (
                <MetaIcon
                    className={classes.resolved}
                    icon={resolved ? "meta-resolved" : "meta-unresolved"}
                    aria-label={resolved ? t("Resolved") : t("Unresolved")}
                />
            )}
            {closed && <MetaTag tagPreset={variables.labels.tagPreset}>{t("Closed")}</MetaTag>}

            {pinned && <MetaTag className={classes.announcementTag}>{t("Announcement")}</MetaTag>}

            {displayQnaStatus && (
                <MetaTag className={getQNAClass(attributes.question.status)}>
                    {t(`${qnaStatus(attributes!.question!.status!)}`)}
                </MetaTag>
            )}

            {shouldShowUserTags &&
                tags?.slice(0, variables.userTags.maxNumber).map((tag, i) => {
                    const query = qs.stringify({
                        domain: "discussions",
                        tagsOptions: [
                            {
                                value: tag.tagID,
                                label: tag.name,
                                tagCode: tag.urlcode,
                            },
                        ],
                    });
                    const searchUrl = `/search?${query}`;
                    const discussionsWithTagFilterUrl = `/discussions?tagID=${tag.tagID}`;
                    return (
                        <MetaTag
                            className={`tag-usertag-${slugify(tag.name)}`}
                            to={customLayoutsForDiscussionListIsEnabled ? discussionsWithTagFilterUrl : searchUrl}
                            key={i}
                            tagPreset={variables.userTags.tagPreset}
                        >
                            {tag.name}
                        </MetaTag>
                    );
                })}
        </>
    );

    return (
        <>
            {!inTile && tagsAndResolvedMetas}
            {displayViewCount && !renderViewCountAsIcon && (
                <MetaItem>
                    <Translate source="<0/> views" c0={countViews} />
                </MetaItem>
            )}
            {displayCommentCount && !renderCommentCountAsIcon && (
                <MetaItem>
                    <Translate source="<0/> comments" c0={countComments} />
                </MetaItem>
            )}
            {displayScore && !renderScoreAsIcon && <MetaItem>{getPointsLabel(score ?? 0)}</MetaItem>}
            {displayStartedByUser && (
                <MetaItem>
                    <Translate
                        source="Started by <0/>"
                        c0={<ProfileLink userFragment={insertUser!} className={metasClasses().metaLink} />}
                    />
                </MetaItem>
            )}
            {displayLastUser && (
                <MetaItem className={cx({ [classes.fullWidth]: inTile })}>
                    <Translate
                        source="Most recent by <0/>"
                        c0={<ProfileLink userFragment={lastUser!} className={metasClasses().metaLink} />}
                    />
                </MetaItem>
            )}
            {displayLastCommentDate && !renderLastCommentDateAsIcon && dateLastComment && (
                <MetaItem>
                    <DateTime timestamp={dateLastComment} />
                </MetaItem>
            )}
            {displayCategory && <MetaLink to={category!.url}> {category!.name} </MetaLink>}
            {displayUnreadCount && (
                <MetaItem>
                    <Notice>
                        {countUnread ? (
                            <Translate source="<0/> new" c0={props.countUnread} />
                        ) : (
                            <Translate source="New" />
                        )}
                    </Notice>
                </MetaItem>
            )}
            {renderViewCountAsIcon && (
                <MetaIcon icon="meta-view" aria-label={t("Views")}>
                    {countViews}
                </MetaIcon>
            )}
            {renderLastCommentDateAsIcon && dateLastComment && (
                <MetaIcon icon="meta-time" aria-label={t("Last comment")}>
                    <DateTime timestamp={dateLastComment} />
                </MetaIcon>
            )}
            {renderScoreAsIcon && (
                <MetaIcon icon="meta-like" aria-label={t("Score")}>
                    {score ?? 0}
                </MetaIcon>
            )}
            {renderCommentCountAsIcon && (
                <MetaIcon icon="meta-comment" aria-label={t("Comments")}>
                    {countComments}
                </MetaIcon>
            )}
        </>
    );
}

function getPointsLabel(points: number): string {
    const label = points === 0 ? t("%s point") : t("%s points");
    return sprintf(label, points);
}
