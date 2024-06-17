/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITriageRecord } from "@dashboard/moderation/CommunityManagementTypes";
import { AssociatedReportMetas } from "@dashboard/moderation/components/AssosciatedReportMetas";
import { TriageInternalStatus } from "@dashboard/moderation/components/TriageFilters.constants";
import { triageListItemClasses } from "@dashboard/moderation/components/TriageListItem.classes";
import apiv2 from "@library/apiv2";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { useToast } from "@library/features/toaster/ToastContext";
import { deletedUserFragment } from "@library/features/__fixtures__/User.Deleted";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownSwitchButton from "@library/flyouts/DropDownSwitchButton";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { ListItem } from "@library/lists/ListItem";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { Metas, MetaItem, MetaIcon } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import ProfileLink from "@library/navigation/ProfileLink";
import LinkAsButton from "@library/routing/LinkAsButton";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Icon } from "@vanilla/icons";

interface IProps {
    triageItem: ITriageRecord;
    onEscalate: (triageItem: ITriageRecord) => void;
}

export function TriageListItem(props: IProps) {
    const { triageItem, onEscalate } = props;
    const classes = triageListItemClasses();
    const isResolved = triageItem.recordInternalStatus?.statusID?.toString() == TriageInternalStatus.RESOLVED;
    const toast = useToast();
    const queryClient = useQueryClient();
    const resolveMutation = useMutation({
        mutationFn: (options: { discussionID: string; internalStatusID: TriageInternalStatus }) => {
            const { discussionID, internalStatusID } = options;
            return apiv2.put(`/discussions/${discussionID}/status`, {
                internalStatusID: internalStatusID,
            });
        },
        onSuccess() {
            queryClient.invalidateQueries(["triageItems"]);
            toast.addToast({
                autoDismiss: true,
                body: "Post marked as resolved.",
            });
        },
    });

    return (
        <div className={classes.container}>
            <div className={classes.main}>
                <ListItem
                    as={"div"}
                    url={`/dashboard/content/triage/${triageItem.recordID}`} // Fix this URL
                    name={triageItem.recordName}
                    description={triageItem.recordHtml ?? triageItem.recordExcerpt}
                    truncateDescription={false}
                    icon={
                        <ProfileLink userFragment={triageItem.recordUser ?? deletedUserFragment()}>
                            <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={triageItem.recordUser} />
                        </ProfileLink>
                    }
                    metas={
                        <>
                            <Metas>
                                <MetaIcon
                                    icon={isResolved ? "cmd-approve" : "cmd-alert"}
                                    aria-label={isResolved ? t("Resolved") : t("Unresolved")}
                                />
                                <MetaItem>
                                    <Translate
                                        source="Posted by <0/> in <1/>"
                                        c0={
                                            <SmartLink
                                                to={`${triageItem.recordUser?.url}`}
                                                className={metasClasses().metaLink}
                                            >
                                                {triageItem.recordUser?.name}
                                            </SmartLink>
                                        }
                                        c1={
                                            <SmartLink
                                                to={`${triageItem.placeRecordUrl}`}
                                                className={metasClasses().metaLink}
                                            >
                                                {triageItem.placeRecordName}
                                            </SmartLink>
                                        }
                                    />
                                </MetaItem>
                                <MetaItem>
                                    <MetaIcon icon="meta-time" style={{ marginLeft: -4 }} />
                                    <DateTime timestamp={triageItem.recordDateInserted}></DateTime>
                                </MetaItem>
                            </Metas>
                            {triageItem.countReports > 0 && (
                                <Metas className={classes.metaLine}>
                                    <AssociatedReportMetas
                                        reasons={triageItem.reportReasons}
                                        countReports={triageItem.countReports}
                                        dateLastReport={triageItem.dateLastReport}
                                    />
                                </Metas>
                            )}
                        </>
                    }
                />
                <div className={classes.quickActions}>
                    {!isResolved && (
                        <ToolTip label={t("Resolve post")}>
                            <Button
                                buttonType={ButtonTypes.ICON_COMPACT}
                                onClick={() => {
                                    resolveMutation.mutate({
                                        discussionID: triageItem.recordID,
                                        internalStatusID: TriageInternalStatus.RESOLVED,
                                    });
                                }}
                            >
                                {resolveMutation.isLoading ? <ButtonLoader /> : <Icon icon="cmd-dismiss" />}
                            </Button>
                        </ToolTip>
                    )}
                    <ToolTip label={t("View post in community")}>
                        <span>
                            <LinkAsButton
                                buttonType={ButtonTypes.ICON_COMPACT}
                                to={triageItem.recordUrl}
                                target="_blank"
                            >
                                <Icon icon="meta-external" />
                            </LinkAsButton>
                        </span>
                    </ToolTip>
                    <DropDown
                        buttonType={ButtonTypes.ICON_COMPACT}
                        flyoutType={FlyoutType.LIST}
                        buttonContents={<Icon icon="navigation-circle-ellipsis" />}
                    >
                        <DropDownSwitchButton
                            label={isResolved ? t("Unresolve") : t("Resolve")}
                            isLoading={resolveMutation.isLoading}
                            onClick={() => {
                                resolveMutation.mutate({
                                    discussionID: triageItem.recordID,
                                    internalStatusID: isResolved
                                        ? TriageInternalStatus.UNRESOLVED
                                        : TriageInternalStatus.RESOLVED,
                                });
                            }}
                            status={isResolved}
                        />
                        <DropDownItemButton
                            onClick={() => {
                                onEscalate(triageItem);
                            }}
                        >
                            {t("Escalate")}
                        </DropDownItemButton>
                        <DropDownItemSeparator />
                        <DropDownItemButton onClick={(e) => null}>
                            <Translate source={"Message <0/>"} c0={triageItem.recordUser?.name} />
                        </DropDownItemButton>
                        <DropDownItemSeparator />
                        <DropDownItemButton
                            onClick={() => {
                                onEscalate(triageItem);
                            }}
                        >
                            {t("Escalate and Assign")}
                        </DropDownItemButton>
                        <DropDownItemButton
                            onClick={() => {
                                onEscalate(triageItem);
                            }}
                        >
                            {t("Escalate to Zendesk")}
                        </DropDownItemButton>
                    </DropDown>
                </div>
            </div>
            <footer className={classes.footer}>
                <div className={classes.actions}>
                    <Button
                        buttonType={ButtonTypes.TEXT_PRIMARY}
                        onClick={() => {
                            onEscalate(triageItem);
                        }}
                    >
                        {t("Escalate")}
                    </Button>
                </div>
            </footer>
        </div>
    );
}
