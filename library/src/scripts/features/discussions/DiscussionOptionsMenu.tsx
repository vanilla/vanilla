/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { t } from "@library/utility/appUtils";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { useUserCanEditDiscussion } from "@library/features/discussions/discussionHooks";
import Permission, {
    hasPermission,
    IPermission,
    IPermissionOptions,
    PermissionMode,
} from "@library/features/users/Permission";
import DiscussionOptionsAnnounce from "@library/features/discussions/DiscussionOptionsAnnounce";
import DiscussionOptionsMove from "@library/features/discussions/DiscussionOptionsMove";
import DiscussionOptionsDelete from "@library/features/discussions/DiscussionOptionsDelete";
import { useUsersState } from "@library/features/users/userModel";
import { DiscussionOptionsClose } from "@library/features/discussions/DiscussionOptionsClose";
import { DiscussionOptionsSink } from "@library/features/discussions/DiscussionOptionsSink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { DiscussionOptionsChangeLog } from "@library/features/discussions/DiscussionOptionsChangeLog";
import DiscussionChangeType from "@library/features/discussions/DiscussionOptionsChangeType";
import { NON_CHANGE_TYPE } from "@library/features/discussions/forms/ChangeTypeDiscussionForm";
import { DiscussionOptionsResolve } from "@library/features/discussions/DiscussionOptionsResolve";

interface IDiscussionOptionItem {
    permission?: IPermission;
    component: React.ComponentType<{ discussion: IDiscussion }>;
}

const additionalDiscussionOptions: IDiscussionOptionItem[] = [];

export function addDiscussionOption(option: IDiscussionOptionItem) {
    additionalDiscussionOptions.push(option);
}

const DiscussionOptionsMenu: FunctionComponent<{ discussion: IDiscussion }> = ({ discussion }) => {
    const canEdit = useUserCanEditDiscussion(discussion);
    const permOptions: IPermissionOptions = {
        resourceType: "category",
        resourceID: discussion.categoryID,
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
    };

    const currentUserID = useUsersState().currentUser.data?.userID;

    const isAuthor = discussion.insertUserID === currentUserID;

    const canModerate = hasPermission("discussions.moderate", permOptions);

    const canClose = canModerate || (isAuthor && hasPermission("discussions.closeOwn", permOptions));

    const canMove = hasPermission("community.moderate", { mode: PermissionMode.GLOBAL });
    const canDelete = hasPermission("discussions.manage", permOptions);
    const canResolve = hasPermission("staff.allow", { mode: PermissionMode.GLOBAL_OR_RESOURCE });

    const items: React.ReactNode[] = [];

    const allowedDiscussionTypes = discussion?.category?.allowedDiscussionTypes ?? [];
    const filteredDiscussionTypes = allowedDiscussionTypes.filter((type) => {
        return !NON_CHANGE_TYPE.includes(type);
    });

    const canChangeType = filteredDiscussionTypes.length > 1 && canEdit;

    if (canEdit) {
        items.push(
            <DropDownItemLink to={`/post/editdiscussion/${discussion.discussionID}`}>{t("Edit")}</DropDownItemLink>,
        );
    }

    if (canDelete) {
        items.push(<DiscussionOptionsDelete discussion={discussion} />);
    }

    items.push(<DropDownItemSeparator />);

    if (canModerate) {
        items.push(<DiscussionOptionsAnnounce discussion={discussion} />);
        items.push(<DiscussionOptionsSink discussion={discussion} />);
    }

    if (canMove) {
        items.push(<DiscussionOptionsMove discussion={discussion} />);
    }

    if (canClose) {
        items.push(<DiscussionOptionsClose discussion={discussion} />);
    }

    if (canChangeType) {
        items.push(<DiscussionChangeType discussion={discussion} />);
    }

    items.push(<DropDownItemSeparator />);

    if (canModerate) {
        items.push(<DiscussionOptionsChangeLog discussion={discussion} />);
    }

    items.push(<DropDownItemSeparator />);

    if (discussion.resolved !== undefined && canResolve) {
        items.push(<DiscussionOptionsResolve discussion={discussion} />);
    }

    // Do the extras
    additionalDiscussionOptions.forEach((option) => {
        if (!option.permission || hasPermission(option.permission.permission, option.permission.options)) {
            items.push(<option.component discussion={discussion} />);
        }
    });

    if (items.length === 0) {
        return <></>;
    }

    return (
        <DropDown name={t("Discussion Options")} flyoutType={FlyoutType.LIST}>
            {items.map((item, i) => {
                return <React.Fragment key={i}>{item}</React.Fragment>;
            })}
        </DropDown>
    );
};

export default DiscussionOptionsMenu;
