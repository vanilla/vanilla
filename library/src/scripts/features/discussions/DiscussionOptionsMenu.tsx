/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import DropDown, { DropDownOpenDirection, FlyoutType } from "@library/flyouts/DropDown";
import { getMeta, t } from "@library/utility/appUtils";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { useUserCanEditDiscussion } from "@library/features/discussions/discussionHooks";
import { hasPermission, IPermission, IPermissionOptions, PermissionMode } from "@library/features/users/Permission";
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
import { DiscussionOptionsTag } from "@library/features/discussions/DiscussionOptionsTag";
import { CollectionsOptionButton } from "@library/featuredCollections/CollectionsOptionButton";
import { CollectionRecordTypes } from "@library/featuredCollections/Collections.variables";

interface IDiscussionOptionItem {
    permission?: IPermission;
    component: React.ComponentType<{ discussion: IDiscussion }>;
    sort?: number;
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

    const items: React.ReactNode[] = [];

    const allowedDiscussionTypes = discussion?.category?.allowedDiscussionTypes ?? [];
    const filteredDiscussionTypes = allowedDiscussionTypes.filter((type) => {
        return !NON_CHANGE_TYPE.includes(type);
    });

    const canChangeType = filteredDiscussionTypes.length > 1 && canEdit;

    const canAddToCollection = hasPermission("community.manage", permOptions);

    if (canEdit) {
        items.push(
            <DropDownItemLink to={`/post/editdiscussion/${discussion.discussionID}`}>{t("Edit")}</DropDownItemLink>,
        );
        if (getMeta("TaggingAdd")) {
            items.push(<DiscussionOptionsTag discussion={discussion} />);
        }
    }

    if (canDelete) {
        items.push(<DiscussionOptionsDelete discussion={discussion} />);
    }

    if (canModerate || canMove || canClose || canChangeType || canAddToCollection) {
        items.push(<DropDownItemSeparator />);

        if (canModerate || canAddToCollection) {
            items.push(<DiscussionOptionsAnnounce discussion={discussion} />);
            if (canAddToCollection) {
                items.push(
                    <CollectionsOptionButton
                        recordID={discussion.discussionID}
                        recordType={CollectionRecordTypes.DISCUSSION}
                        record={discussion}
                    />,
                );
            }
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

        if (canModerate) {
            items.push(<DropDownItemSeparator />);
            items.push(<DiscussionOptionsChangeLog discussion={discussion} />);
        }
    }

    if (additionalDiscussionOptions.length) {
        let permissionCheckedItems: React.ReactNode[] = [];
        // Do the extras
        additionalDiscussionOptions
            .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
            .forEach((option) => {
                if (!option.permission || hasPermission(option.permission.permission, option.permission.options)) {
                    permissionCheckedItems.push(<option.component discussion={discussion} />);
                }
            });
        if (permissionCheckedItems.length > 0) {
            items.push(<DropDownItemSeparator />);
            items.push(...permissionCheckedItems);
        }
    }

    if (items.length === 0) {
        return <></>;
    }

    return (
        <DropDown name={t("Discussion Options")} flyoutType={FlyoutType.LIST} asReachPopover>
            {items.map((item, i) => {
                return <React.Fragment key={i}>{item}</React.Fragment>;
            })}
        </DropDown>
    );
};

export default DiscussionOptionsMenu;
