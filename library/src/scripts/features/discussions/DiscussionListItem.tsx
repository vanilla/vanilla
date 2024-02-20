/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { cx } from "@emotion/css";
import DiscussionBookmarkToggle from "@library/features/discussions/DiscussionBookmarkToggle";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import {
    discussionListVariables,
    IDiscussionItemOptions,
} from "@library/features/discussions/DiscussionList.variables";
import { useCurrentUserSignedIn } from "@library/features/users/userHooks";
import { UserPhoto } from "@library/headers/mebox/pieces/UserPhoto";
import { ListItem } from "@library/lists/ListItem";
import ProfileLink from "@library/navigation/ProfileLink";
import { t } from "@vanilla/i18n";
import React, { useMemo, useState } from "react";
import DiscussionOptionsMenu from "@library/features/discussions/DiscussionOptionsMenu";
import DiscussionVoteCounter from "@library/features/discussions/DiscussionVoteCounter";
import { PermissionMode } from "@library/features/users/Permission";
import { ReactionUrlCode } from "@dashboard/@types/api/reaction";
import { getMeta } from "@library/utility/appUtils";
import CheckBox from "@library/forms/Checkbox";
import { useDiscussionCheckBoxContext } from "@library/features/discussions/DiscussionCheckboxContext";
import { ToolTip } from "@library/toolTip/ToolTip";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { pointerEventsClass } from "@library/styles/styleHelpersFeedback";
import { ListItemIconPosition } from "@library/lists/ListItem.variables";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { DiscussionListItemMeta } from "@library/features/discussions/DiscussionListItemMeta";
import { useDiscussionActions } from "@library/features/discussions/DiscussionActions";
import { useDiscussionsDispatch } from "@library/features/discussions/discussionsReducer";

interface IProps {
    discussion: IDiscussion;
    noCheckboxes?: boolean;
    className?: string;
    asTile?: boolean;
    discussionOptions?: IDiscussionItemOptions;
    disableButtonsInItems?: boolean;
}

export default function DiscussionListItem(props: IProps) {
    const { discussion } = props;

    const { hasPermission } = usePermissionsContext();

    const { getDiscussionByIDs } = useDiscussionActions();
    const dispatch = useDiscussionsDispatch();

    const classes = discussionListClasses(props.discussionOptions, props.asTile);
    const variables = discussionListVariables(props.discussionOptions);
    const currentUserSignedIn = useCurrentUserSignedIn();
    const checkBoxContext = useDiscussionCheckBoxContext();
    const hasUnread = discussion.unread || (discussion.countUnread !== undefined && discussion.countUnread > 0);
    const goToLatest = getMeta("ui.autoOffsetComments", true);

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
        discussion.reactions?.some(({ urlcode }) =>
            [ReactionUrlCode.UP, ReactionUrlCode.DOWN].includes(urlcode as ReactionUrlCode),
        )
    ) {
        const availableReactionsCount = discussion.reactions.filter(({ urlcode }) =>
            [ReactionUrlCode.UP, ReactionUrlCode.DOWN].includes(urlcode as ReactionUrlCode),
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
                    <DiscussionOptionsMenu
                        discussion={discussion}
                        onMutateSuccess={async () => {
                            await dispatch(getDiscussionByIDs({ discussionIDs: [discussionID] }));
                        }}
                    />
                </>
            )}
        </>
    );

    const discussionUrl = currentUserSignedIn && goToLatest ? `${discussion.url}#latest` : discussion.url;

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

export function qnaStatus(status) {
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
