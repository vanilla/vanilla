/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import DiscussionBookmarkToggle from "@library/features/discussions/DiscussionBookmarkToggle";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { useCurrentUserSignedIn } from "@library/features/users/userHooks";
import { UserPhoto } from "@library/headers/mebox/pieces/UserPhoto";
import { ListItem } from "@library/lists/ListItem";
import { ListItemIconPosition, listItemVariables } from "@library/lists/ListItem.variables";
import { MetaIcon, MetaItem } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import { Tag } from "@library/metas/Tags";
import ProfileLink from "@library/navigation/ProfileLink";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import React from "react";
import DiscussionOptionsMenu from "@library/features/discussions/DiscussionOptionsMenu";
import { Variables } from "@library/styles/Variables";
import qs from "qs";
import { Icon } from "@vanilla/icons";

interface IProps {
    discussion: IDiscussion;
}

export default function DiscussionListItem(props: IProps) {
    const { discussion } = props;
    const classes = discussionListClasses();
    const variables = discussionListVariables();
    const currentUserSignedIn = useCurrentUserSignedIn();

    let icon = <UserPhoto userInfo={discussion.insertUser} size={variables.profilePhoto.size} />;

    if (discussion.insertUser) {
        icon = <ProfileLink userFragment={discussion.insertUser}>{icon}</ProfileLink>;
    }

    // if (discussion.type === "idea") {
    //     icon = (
    //         <>
    //             {icon}
    //             <DiscussionVoteCounter discussion={discussion} className={classes.counterPosition} />
    //         </>
    //     );
    // }

    const actions = (
        <>
            {currentUserSignedIn && <DiscussionBookmarkToggle discussion={discussion} />}
            <DiscussionOptionsMenu discussion={discussion} />
        </>
    );
    return (
        <ListItem
            url={discussion.url}
            name={discussion.name}
            nameClassName={cx(classes.title, { isRead: !discussion.unread && currentUserSignedIn })}
            description={discussion.excerpt}
            metas={<DiscussionListItemMeta {...discussion} />}
            actions={actions}
            icon={icon}
        ></ListItem>
    );
}

function qnaStatus(status) {
    switch (status) {
        case "unanswered":
            return "Unanswered";
        case "answered":
            return "Answered";
        case "accepted":
            return "Accepted Answer";
        default:
            const capitalizedStatus = status.charAt(0).toUpperCase() + status.slice(1);
            return capitalizedStatus;
    }
}

function DiscussionListItemMeta(props: IDiscussion) {
    const {
        item: {
            metas: { display, asIcons: renderAsIcons },
        },
    } = discussionListVariables();

    const classes = discussionListClasses();
    const variables = discussionListVariables();
    //Check if icon is positioned in metalist
    const iconPositionMeta = listItemVariables().options.iconPosition === ListItemIconPosition.META;

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
    } = props;

    const displayUnreadCount = unread || (countUnread !== undefined && countUnread > 0 && display.unreadCount);

    const displayCategory = !!category && display.category;

    const displayStartedByUser = !!insertUser && !(countComments > 0) && display.startedByUser;
    const displayLastUser = !displayStartedByUser && !!lastUser && display.lastUser;

    const displayQnaStatus = !!attributes?.question?.status && display.qnaStatus;

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

    return (
        <>
            {closed && <Tag>{t("Closed")}</Tag>}

            {pinned && <Tag>{t("Announcement")}</Tag>}

            {displayQnaStatus && <Tag>{t(`${qnaStatus(attributes!.question!.status!)}`)}</Tag>}

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
                    return (
                        <SmartLink to={searchUrl} key={i} className={classes.userTag}>
                            <Tag>{tag.name}</Tag>
                        </SmartLink>
                    );
                })}

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

            {displayScore && !renderScoreAsIcon && (
                <MetaItem>
                    <Translate source="<0/> reactions" c0={score ?? 0} />
                </MetaItem>
            )}

            {displayStartedByUser && (
                <MetaItem>
                    <Translate source="Started by <0/>" c0={<ProfileLink userFragment={insertUser!} />} />
                </MetaItem>
            )}

            {displayLastUser && (
                <MetaItem>
                    <Translate source="Most recent by <0/>" c0={<ProfileLink userFragment={lastUser!} />} />
                </MetaItem>
            )}

            {displayCategory && (
                <MetaItem>
                    <SmartLink to={category!.url}>{category!.name}</SmartLink>
                </MetaItem>
            )}

            {displayUnreadCount && (
                <MetaItem>
                    <Notice>
                        {unread ? <Translate source="New" /> : <Translate source="<0/> new" c0={props.countUnread} />}
                    </Notice>
                </MetaItem>
            )}

            {displayLastCommentDate && !renderLastCommentDateAsIcon && (
                <MetaItem>
                    <DateTime timestamp={dateLastComment} />
                </MetaItem>
            )}

            {renderViewCountAsIcon && (
                <MetaIcon>
                    <Icon icon="meta-view" aria-label={t("Views")} /> {countViews}
                </MetaIcon>
            )}

            {renderLastCommentDateAsIcon && (
                <MetaIcon>
                    <Icon icon="meta-time" aria-label={t("Last comment")} /> <DateTime timestamp={dateLastComment} />
                </MetaIcon>
            )}

            {renderScoreAsIcon && (
                <MetaIcon>
                    <Icon icon="meta-like" aria-label={t("Score")} /> {score ?? 0}
                </MetaIcon>
            )}

            {renderCommentCountAsIcon && (
                <MetaIcon>
                    <Icon icon="meta-comment" aria-label={t("Comments")} /> {countComments}
                </MetaIcon>
            )}
        </>
    );
}
