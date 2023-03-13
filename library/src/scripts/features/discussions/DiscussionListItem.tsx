/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import DiscussionBookmarkToggle from "@library/features/discussions/DiscussionBookmarkToggle";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import {
    discussionListVariables,
    IDiscussionItemOptions,
} from "@library/features/discussions/DiscussionList.variables";
import { useCurrentUserSignedIn } from "@library/features/users/userHooks";
import { UserPhoto } from "@library/headers/mebox/pieces/UserPhoto";
import { ListItem } from "@library/lists/ListItem";
import { MetaIcon, MetaItem, MetaLink, MetaTag } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import ProfileLink from "@library/navigation/ProfileLink";
import { t } from "@vanilla/i18n";
import React, { useMemo, useState } from "react";
import DiscussionOptionsMenu from "@library/features/discussions/DiscussionOptionsMenu";
import DiscussionVoteCounter from "@library/features/discussions/DiscussionVoteCounter";
import qs from "qs";
import { hasPermission, PermissionMode } from "@library/features/users/Permission";
import { ReactionUrlCode } from "@dashboard/@types/api/reaction";
import DateTime from "@library/content/DateTime";
import { metasClasses } from "@library/metas/Metas.styles";
import { getMeta } from "@library/utility/appUtils";
import CheckBox from "@library/forms/Checkbox";
import { useDiscussionCheckBoxContext } from "@library/features/discussions/DiscussionCheckboxContext";
import { ToolTip } from "@library/toolTip/ToolTip";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { pointerEventsClass } from "@library/styles/styleHelpersFeedback";
import { slugify } from "@vanilla/utils";
import { ListItemIconPosition } from "@library/lists/ListItem.variables";
interface IProps {
    discussion: IDiscussion;
    noCheckboxes?: boolean;
    className?: string;
    asTile?: boolean;
    discussionOptions?: IDiscussionItemOptions;
    disableButtonsInItems?: boolean;
}
interface IDiscussionItemMetaProps extends IDiscussion {
    inTile?: boolean;
    discussionOptions?: IDiscussionItemOptions;
}

export default function DiscussionListItem(props: IProps) {
    const { discussion } = props;

    const classes = discussionListClasses(props.discussionOptions, props.asTile);
    const variables = discussionListVariables(props.discussionOptions);
    const currentUserSignedIn = useCurrentUserSignedIn();
    const checkBoxContext = useDiscussionCheckBoxContext();
    const hasUnread = discussion.unread || (discussion.countUnread !== undefined && discussion.countUnread > 0);

    let iconView = <UserPhoto userInfo={discussion.insertUser} size={variables.profilePhoto.size} />;

    if (discussion.insertUser) {
        iconView = <ProfileLink userFragment={discussion.insertUser}>{iconView}</ProfileLink>;
    }

    const iconClass = cx({ [pointerEventsClass()]: props.disableButtonsInItems });

    let icon: React.ComponentProps<typeof ListItem>["icon"] = null;
    let secondIcon: React.ComponentProps<typeof ListItem>["secondIcon"] = null;

    if (variables.item.options.iconPosition !== ListItemIconPosition.HIDDEN) {
        icon = <div className={cx(iconClass, classes.userIcon)}>{iconView}</div>;
    }

    if (
        currentUserSignedIn &&
        discussion.type === "idea" &&
        discussion.reactions?.some(({ urlcode }) => [ReactionUrlCode.UP, ReactionUrlCode.DOWN].includes(urlcode))
    ) {
        const availableReactionsCount = discussion.reactions.filter(({ urlcode }) =>
            [ReactionUrlCode.UP, ReactionUrlCode.DOWN].includes(urlcode),
        ).length;
        const ideationCounterContent = (
            <DiscussionVoteCounter
                className={cx(
                    classes.iconAndVoteCounterWrapper(availableReactionsCount as 1 | 2 | undefined),
                    iconClass,
                )}
                discussion={discussion}
            />
        );
        secondIcon = ideationCounterContent;
    }

    const actions = (
        <>
            {currentUserSignedIn && (
                <>
                    <DiscussionBookmarkToggle discussion={discussion} />
                    <DiscussionOptionsMenu discussion={discussion} />
                </>
            )}
        </>
    );

    const discussionUrl = currentUserSignedIn ? `${discussion.url}#latest` : discussion.url;

    //check if the user has permission to see checkbox
    const canUseCheckboxes =
        !props.noCheckboxes &&
        hasPermission("discussions.manage", {
            resourceType: "category",
            resourceID: discussion.categoryID,
            mode: PermissionMode.RESOURCE_IF_JUNCTION,
        }) &&
        getMeta("ui.useAdminCheckboxes", false);

    const { discussionID } = discussion;
    const isRowChecked = checkBoxContext.checkedDiscussionIDs.includes(discussionID);
    const isPendingAction = checkBoxContext.pendingActionIDs.includes(discussionID);

    const [disabledNote, setDisabledNote] = useState<string | null>(null);

    const isCheckboxDisabled = useMemo(() => {
        const BULK_ACTION_LIMIT = 50;
        // Check for selection limit
        const isLimitReached = !isRowChecked && checkBoxContext.checkedDiscussionIDs.length >= BULK_ACTION_LIMIT;
        setDisabledNote((prevState) => {
            if (isLimitReached) {
                return t("You have reached the maximum selection amount.");
            }
            if (isPendingAction) {
                return t("This discussion is still being processed.");
            }
            return prevState;
        });

        return isLimitReached || isPendingAction;
    }, [checkBoxContext, isRowChecked, isPendingAction]);

    return (
        <ListItem
            url={discussionUrl}
            name={discussion.name}
            className={cx(isRowChecked || isPendingAction ? classes.checkedboxRowStyle : undefined, props.className)}
            nameClassName={cx(classes.title, { isRead: !hasUnread && currentUserSignedIn })}
            description={props.discussionOptions?.excerpt?.display === false ? "" : discussion.excerpt}
            metas={
                <DiscussionListItemMeta
                    {...discussion}
                    inTile={props.asTile}
                    discussionOptions={props.discussionOptions}
                />
            }
            actions={actions}
            icon={icon}
            secondIcon={secondIcon}
            options={variables.item.options}
            as={props.asTile ? "div" : undefined}
            featuredImage={variables.item.featuredImage}
            image={discussion.image}
            asTile={props.asTile}
            disableButtonsInItems={props.disableButtonsInItems}
            // TODO: Disable this until the feature is finished.
            checkbox={
                canUseCheckboxes ? (
                    <ConditionalWrap
                        condition={isCheckboxDisabled && !!disabledNote}
                        component={ToolTip}
                        componentProps={{ label: disabledNote }}
                    >
                        {/* This span is required for the conditional tooltip */}
                        <span>
                            <CheckBox
                                checked={isRowChecked || isPendingAction}
                                label={`Select ${discussion.name}`}
                                hideLabel={true}
                                disabled={isCheckboxDisabled}
                                onChange={(e) => {
                                    if (e.target.checked) {
                                        checkBoxContext.addCheckedDiscussionsByIDs(discussionID);
                                    } else {
                                        checkBoxContext.removeCheckedDiscussionsByIDs(discussionID);
                                    }
                                }}
                            />
                        </span>
                    </ConditionalWrap>
                ) : undefined
            }
        ></ListItem>
    );
}

function qnaStatus(status) {
    switch (status) {
        case "unanswered":
        case "rejected":
            return "Q&A Question";
        case "answered":
            return "Q&A Answered";
        case "accepted":
            return "Q&A Accepted";
        default:
            const capitalizedStatus = status.charAt(0).toUpperCase() + status.slice(1);
            return capitalizedStatus;
    }
}

function DiscussionListItemMeta(props: IDiscussionItemMetaProps) {
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
        ((unread && !inTile) || (countUnread !== undefined && countUnread > 0 && display.unreadCount));

    const displayCategory = !!category && display.category;

    const displayStartedByUser = !!insertUser && display.startedByUser;
    // By default "lastUser" is "insertUser", we don't want ot display it twice if no-one has commented.
    const displayLastUser = countComments > 0 && !!lastUser && display.lastUser;

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

    const canResolve = hasPermission("staff.allow", { mode: PermissionMode.GLOBAL_OR_RESOURCE });
    const displayResolved = resolved !== undefined && canResolve && display.resolved;

    const tagsAndResolvedMetas = (
        <>
            {displayResolved && (
                <MetaIcon
                    className={classes.resolved}
                    icon={resolved ? "meta-resolved" : "meta-unresolved"}
                    aria-label={resolved ? t("Resolved") : t("Unresolved")}
                />
            )}
            {closed && (
                <MetaTag className={"tag-closed"} tagPreset={variables.labels.tagPreset}>
                    {t("Closed")}
                </MetaTag>
            )}

            {pinned && (
                <MetaTag className={"tag-announcement"} tagPreset={variables.labels.tagPreset}>
                    {t("Announcement")}
                </MetaTag>
            )}

            {displayQnaStatus && (
                <MetaTag className={`tag-qna-${attributes.question.status}`} tagPreset={variables.labels.tagPreset}>
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
                    return (
                        <MetaTag
                            className={`tag-usertag-${slugify(tag.name)}`}
                            to={searchUrl}
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
            {displayScore && !renderScoreAsIcon && (
                <MetaItem>
                    <Translate source="<0/> reactions" c0={score ?? 0} />
                </MetaItem>
            )}
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
