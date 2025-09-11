/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { getMeta, t } from "@library/utility/appUtils";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { useUserCanStillEditDiscussionOrComment } from "@library/features/discussions/discussionHooks";
import { IPermission, IPermissionOptions, PermissionMode } from "@library/features/users/Permission";
import DiscussionOptionsAnnounce from "@library/features/discussions/DiscussionOptionsAnnounce";
import DiscussionOptionsDelete from "@library/features/discussions/DiscussionOptionsDelete";
import { useCurrentUserID } from "@library/features/users/userHooks";
import { DiscussionOptionsClose } from "@library/features/discussions/DiscussionOptionsClose";
import { DiscussionOptionsSink } from "@library/features/discussions/DiscussionOptionsSink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { DiscussionOptionsChangeLog } from "@library/features/discussions/DiscussionOptionsChangeLog";
import { DiscussionOptionsTag } from "@library/features/discussions/DiscussionOptionsTag";
import { CollectionsOptionButton } from "@library/featuredCollections/CollectionsOptionButton";
import { CollectionRecordTypes } from "@library/featuredCollections/Collections.variables";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { DiscussionOptionsChangeAuthor } from "@library/features/discussions/DiscussionOptionsChangeAuthor";
import DiscussionOptionsDismiss from "@library/features/discussions/DiscussionOptionsDismiss";
import DiscussionOptionsBump from "@library/features/discussions/DiscussionOptionsBump";
import {
    WriteableIntegrationContextProvider,
    useWriteableAttachmentIntegrations,
} from "@library/features/discussions/integrations/Integrations.context";
import { IntegrationButtonAndModal } from "@library/features/discussions/integrations/Integrations";
import { NON_CHANGE_TYPE } from "@library/features/discussions/forms/ChangeTypeDiscussionForm.constants";
import { Icon } from "@vanilla/icons";
import { ToolTip } from "@library/toolTip/ToolTip";
import { ReportRecordOption } from "@library/features/discussions/ReportRecordOption";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DiscussionOptionsResolve } from "@library/features/discussions/DiscussionOptionsResolve";
import { css } from "@emotion/css";
import { useQueryClient } from "@tanstack/react-query";
import { DiscussionOptionsMute } from "@library/features/discussions/DiscussionOptionsMute";
import DiscussionOptionsPostSettings from "@library/features/discussions/DiscussionOptionsPostSettings";

interface IDiscussionOptionItem {
    permission?: IPermission;
    component: React.ComponentType<{ discussion: IDiscussion; onSuccess?: () => Promise<void> }>;
    sort?: number;
    group?: "firstGroup" | "moderationGroup" | "statusGroup" | "flagGroup";
}

const additionalDiscussionOptions: IDiscussionOptionItem[] = [];

export function addDiscussionOption(option: IDiscussionOptionItem) {
    additionalDiscussionOptions.push(option);
}

interface IDiscussionOptionsMenuProps {
    discussion: IDiscussion;
    /** Callback invoked whenever a PUT or PATCH action on the discussion is successful.
     *
     * Useful on the new discussion thread page, where we read discussion state from react-query and not redux,
     * and need to invalidate certain queries when a mutation is successful.
     */
    onMutateSuccess?: () => Promise<void>;
    onDiscussionPage?: boolean;
}

const reportButtonAlignment = css({
    "&:not(:last-child)": {
        marginInlineEnd: -8,
    },
    "@media (max-width: 806px)": {
        "&:not(:last-child)": {
            marginInlineEnd: "initial",
        },
    },
});

const DiscussionOptionsMenu: FunctionComponent<IDiscussionOptionsMenuProps> = ({
    discussion,
    onMutateSuccess,
    onDiscussionPage,
}) => {
    const { hasPermission } = usePermissionsContext();
    const queryClient = useQueryClient();

    const { canStillEdit, humanizedRemainingTime } = useUserCanStillEditDiscussionOrComment(discussion);
    const permOptions: IPermissionOptions = {
        resourceType: "category",
        resourceID: discussion.categoryID,
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
    };

    const taggingEnabled = getMeta("tagging.enabled", false);

    const writeableIntegrations = useWriteableAttachmentIntegrations();

    const currentUserID = useCurrentUserID();

    const isAuthor = discussion.insertUserID === currentUserID;

    const canModerate = hasPermission("discussions.moderate", permOptions);

    const canClose = canModerate || (isAuthor && hasPermission("discussions.closeOwn", permOptions));

    const canChangeAuthor = hasPermission("site.manage", permOptions);
    const canEdit = hasPermission("discussions.edit", permOptions);
    const canDelete = hasPermission("discussions.delete", permOptions);

    const canBump = hasPermission("curation.manage");

    const canReport = hasPermission("flag.add") && getMeta("featureFlags.escalations.Enabled", false);

    const items: React.ReactNode[] = [];

    // These items appear in the first section in the menu, Edit - Dismiss - Move - Resolve - Delete - Tag etc
    const firstGroupItems: React.ReactNode[] = [];

    const canAddToCollection = hasPermission("community.manage", { mode: PermissionMode.GLOBAL_OR_RESOURCE });

    if (canStillEdit) {
        firstGroupItems.push(
            <DropDownItemLink to={`/post/editdiscussion/${discussion.discussionID}`}>
                {humanizedRemainingTime}
            </DropDownItemLink>,
        );
    }

    if (discussion.pinned) {
        firstGroupItems.push(<DiscussionOptionsDismiss discussion={discussion} onSuccess={onMutateSuccess} />);
    }

    if (canDelete) {
        firstGroupItems.push(
            <DiscussionOptionsDelete discussion={discussion} redirectAfterDelete={onDiscussionPage} />,
        );
    }

    if (canEdit) {
        firstGroupItems.push(
            <DiscussionOptionsPostSettings
                discussion={discussion}
                onSuccess={async () => {
                    await onMutateSuccess?.();
                    if (onDiscussionPage) {
                        // Refresh layout to show any change
                        await queryClient.invalidateQueries({ queryKey: ["layoutSpec", "lookup"] });
                    }
                }}
            />,
        );
    }

    if (canStillEdit && taggingEnabled) {
        firstGroupItems.push(<DiscussionOptionsTag discussion={discussion} onSuccess={onMutateSuccess} />);
    }

    const canResolve = hasPermission("staff.allow") && getMeta("triage.enabled", false);
    if (canResolve) {
        firstGroupItems.push(<DiscussionOptionsResolve discussion={discussion} onSuccess={onMutateSuccess} />);
    }

    // These items appear in the second section in the menu, after the edit et al but before the items added by plugins
    const moderationItems: React.ReactNode[] = [];

    if (canModerate || canAddToCollection) {
        moderationItems.push(<DiscussionOptionsAnnounce discussion={discussion} onSuccess={onMutateSuccess} />);
    }

    if (canChangeAuthor) {
        moderationItems.push(<DiscussionOptionsChangeAuthor discussion={discussion} onSuccess={onMutateSuccess} />);
    }

    if (canAddToCollection) {
        moderationItems.push(
            <CollectionsOptionButton
                recordID={discussion.discussionID}
                recordType={CollectionRecordTypes.DISCUSSION}
                record={discussion}
            />,
        );
    }

    // These items appear in the third section in the menu, after moderation items
    const statusItems: React.ReactNode[] = [];

    statusItems.push(<DiscussionOptionsMute discussion={discussion} onSuccess={onMutateSuccess} />);

    if (canBump) {
        statusItems.push(<DiscussionOptionsBump discussion={discussion} onSuccess={onMutateSuccess} />);
    }

    if (canModerate || canAddToCollection) {
        statusItems.push(<DiscussionOptionsSink discussion={discussion} onSuccess={onMutateSuccess} />);
    }

    if (canClose) {
        statusItems.push(<DiscussionOptionsClose discussion={discussion} onSuccess={onMutateSuccess} />);
    }

    // These items appear in the forth section in the menu, after status items
    const changeLogItems: React.ReactNode[] = [];

    if (canModerate) {
        changeLogItems.push(<DiscussionOptionsChangeLog discussion={discussion} />);
    }

    let permissionCheckedAndIntegrationItems: React.ReactNode[] = [];

    // these items appear in the end if the dropdown with report
    const extraFlagItems: React.ReactNode[] = [];

    if (additionalDiscussionOptions.length) {
        // Do the extras
        additionalDiscussionOptions
            .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
            .forEach((option) => {
                if (!option.permission || hasPermission(option.permission.permission, option.permission.options)) {
                    switch (option.group) {
                        case "firstGroup":
                            firstGroupItems.splice(
                                option.sort ?? firstGroupItems.length,
                                0,
                                <option.component discussion={discussion} onSuccess={onMutateSuccess} />,
                            );
                            break;
                        case "moderationGroup":
                            moderationItems.splice(
                                option.sort ?? moderationItems.length,
                                0,
                                <option.component discussion={discussion} onSuccess={onMutateSuccess} />,
                            );
                            break;
                        case "statusGroup":
                            statusItems.splice(
                                option.sort ?? statusItems.length,
                                0,
                                <option.component discussion={discussion} onSuccess={onMutateSuccess} />,
                            );
                            break;
                        case "flagGroup": // this ones will go with report
                            extraFlagItems.push(
                                <option.component discussion={discussion} onSuccess={onMutateSuccess} />,
                            );
                            break;
                        default:
                            permissionCheckedAndIntegrationItems.push(
                                <option.component discussion={discussion} onSuccess={onMutateSuccess} />,
                            );
                            break;
                    }
                }
            });
    }

    items.push(...firstGroupItems);

    if (moderationItems.length > 0) {
        items.push(<DropDownItemSeparator />);
        items.push(...moderationItems);
    }

    if (statusItems.length > 0) {
        items.push(<DropDownItemSeparator />);
        items.push(...statusItems);
    }

    if (changeLogItems.length > 0) {
        items.push(<DropDownItemSeparator />);
        items.push(...changeLogItems);
    }

    writeableIntegrations
        .filter(({ recordTypes }) => recordTypes.includes("discussion"))
        .filter(({ writeableContentScope }) => (writeableContentScope === "own" ? isAuthor : true))
        .forEach(({ attachmentType }) => {
            permissionCheckedAndIntegrationItems.push(
                <WriteableIntegrationContextProvider
                    recordType="discussion"
                    attachmentType={attachmentType}
                    recordID={discussion.discussionID}
                >
                    <IntegrationButtonAndModal onSuccess={onMutateSuccess} />
                </WriteableIntegrationContextProvider>,
            );
        });

    if (permissionCheckedAndIntegrationItems.length > 0) {
        items.push(<DropDownItemSeparator />);
        items.push(...permissionCheckedAndIntegrationItems);
    }

    if (items.length > 0 && canReport) {
        items.push(<DropDownItemSeparator />);
        items.push(
            <ReportRecordOption
                recordName={discussion.name}
                recordType={"discussion"}
                recordID={discussion.discussionID}
                onSuccess={onMutateSuccess}
                placeRecordType="category"
                placeRecordID={discussion.categoryID}
                onDiscussionPage={onDiscussionPage}
            />,
        );
        items.push(...extraFlagItems);
    }

    return (
        <>
            {canReport ? (
                <ReportRecordOption
                    recordName={discussion.name}
                    recordType={"discussion"}
                    recordID={discussion.discussionID}
                    onSuccess={onMutateSuccess}
                    placeRecordType="category"
                    placeRecordID={discussion.categoryID}
                    customTrigger={(props) => {
                        return (
                            <ToolTip label={t("Report content")}>
                                <Button
                                    buttonType={ButtonTypes.ICON}
                                    onClick={props.onClick}
                                    className={reportButtonAlignment}
                                >
                                    <Icon icon="report-content" />
                                </Button>
                            </ToolTip>
                        );
                    }}
                    onDiscussionPage={onDiscussionPage}
                />
            ) : null}
            {items.length > 0 && (
                <DropDown
                    buttonContents={<Icon icon="options-menu" />}
                    name={t("Discussion Options")}
                    flyoutType={FlyoutType.LIST}
                    asReachPopover
                >
                    {items.map((item, i) => {
                        return <React.Fragment key={i}>{item}</React.Fragment>;
                    })}
                </DropDown>
            )}
        </>
    );
};

export default DiscussionOptionsMenu;
