/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useEscalationQuery, useEscalationMutation } from "@dashboard/moderation/CommunityManagement.hooks";
import { EscalationStatus, IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import {
    escalationActionPanelClasses,
    getStatusClasses,
} from "@dashboard/moderation/components/EscalationActions.classes";
import { IMessageInfo, MessageAuthorModal } from "@dashboard/moderation/components/MessageAuthorModal";
import { cx } from "@emotion/css";
import { IntegrationButtonAndModal } from "@library/features/discussions/integrations/Integrations";
import {
    useWriteableAttachmentIntegrations,
    WriteableIntegrationContextProvider,
} from "@library/features/discussions/integrations/Integrations.context";
import DropDown, { DropDownOpenDirection, FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { DropDownArrow } from "@vanilla/ui/src/forms/shared/DropDownArrow";
import { labelize } from "@vanilla/utils";
import { useState } from "react";

interface IProps {
    escalationID: IEscalation["escalationID"];
}

export function EscalationActions(props: IProps) {
    const classes = escalationActionPanelClasses();
    const escalationQuery = useEscalationQuery(props.escalationID);
    const escalationMutation = useEscalationMutation(props.escalationID);
    const writeableIntegrations = useWriteableAttachmentIntegrations();

    const [authorMessage, setAuthorMessage] = useState<IMessageInfo | null>(null);

    const statusOptions = Object.values(EscalationStatus);

    const updateStatus = (status: EscalationStatus) => {
        escalationMutation.mutate({
            payload: {
                status,
            },
        });
    };

    const queryClient = useQueryClient();

    return (
        <div className={classes.layout}>
            <DropDown
                buttonType={ButtonTypes.OUTLINE}
                buttonContents={
                    <>
                        {!escalationQuery.isLoading && escalationQuery.data ? (
                            labelize(escalationQuery.data?.status)
                        ) : (
                            <ButtonLoader />
                        )}
                        <DropDownArrow />
                    </>
                }
                buttonClassName={cx(classes.statusButton, getStatusClasses(escalationQuery.data?.status))}
                flyoutType={FlyoutType.LIST}
                openDirection={DropDownOpenDirection.BELOW_RIGHT}
                contentsClassName={classes.contentPanelOverrides}
            >
                {statusOptions.map((status) => {
                    return (
                        <DropDownItemButton key={status} onClick={() => updateStatus(status)}>
                            {labelize(status)}
                        </DropDownItemButton>
                    );
                })}
            </DropDown>
            <DropDown
                buttonType={ButtonTypes.STANDARD}
                buttonContents={
                    <span className={cx(classes.statusButton, escalationQuery?.status)}>
                        {t("Actions")}
                        <DropDownArrow />
                    </span>
                }
                flyoutType={FlyoutType.LIST}
                openDirection={DropDownOpenDirection.BELOW_LEFT}
                contentsClassName={classes.contentPanelOverrides}
            >
                <DropDownItemButton
                    onClick={() => {
                        const { userID, url } = escalationQuery.data?.recordUser ?? {};
                        if (userID && url) {
                            setAuthorMessage({ userID, url });
                        }
                    }}
                >
                    {t("Message Post Author")}
                </DropDownItemButton>

                {escalationQuery.data && (
                    <DropDownItemButton
                        onClick={() =>
                            escalationMutation.mutateAsync({
                                payload: { recordIsLive: !escalationQuery.data.recordIsLive },
                            })
                        }
                    >
                        {escalationQuery.data.recordIsLive ? t("Remove Post") : t("Restore Post")}
                    </DropDownItemButton>
                )}

                {escalationQuery.data &&
                    writeableIntegrations
                        .filter(({ recordTypes }) => recordTypes.includes("escalation"))

                        .map(({ attachmentType }) => {
                            return (
                                <WriteableIntegrationContextProvider
                                    key={attachmentType}
                                    recordType="escalation"
                                    attachmentType={attachmentType}
                                    recordID={escalationQuery.data.escalationID}
                                >
                                    <IntegrationButtonAndModal
                                        onSuccess={() => {
                                            queryClient.invalidateQueries(["escalations"]);
                                            return Promise.resolve();
                                        }}
                                    />
                                </WriteableIntegrationContextProvider>
                            );
                        })}
            </DropDown>
            <MessageAuthorModal
                messageInfo={authorMessage}
                isVisible={!!authorMessage}
                onClose={() => setAuthorMessage(null)}
            />
        </div>
    );
}
