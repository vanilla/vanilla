/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import React, { useState } from "react";
import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { CommunityMemberInput } from "@vanilla/addon-vanilla/forms/CommunityMemberInput";
import ErrorMessages from "@library/forms/ErrorMessages";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { useMutation } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import { ITestEmailPayload, IEmailSettings } from "@dashboard/emailSettings/types";
import { useToast } from "@library/features/toaster/ToastContext";
import { accountSettingsClasses } from "@library/accountSettings/AccountSettings.classes";
import dashboardAddEditUserClasses from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser.classes";
import Message from "@library/messages/Message";
import { IApiError } from "@library/@types/api/core";

interface IProps {
    settings: IEmailSettings | {};
    onCancel(): void;
}

interface ITestDigestPayload extends ITestEmailPayload {
    destinationUserID: number;
    afterInput?: React.ReactNode;
}

export default function TestDigestModal(props: IProps) {
    const { settings, onCancel } = props;
    const [destinationUserID, setDestinationUserID] = useState<number | null>(null);
    const [destinationAddress, setDestinationAddress] = useState<string>("");
    const toast = useToast();

    const testEmailMutation = useMutation<any, IApiError>({
        mutationKey: ["sendTestEmail"],
        mutationFn: async () => {
            return await apiv2.post<ITestDigestPayload>("emails/send-test-digest", {
                destinationUserID,
                destinationAddress,
                deliveryDate: new Date(),
                from: {
                    supportName: settings["outgoingEmails.supportName"],
                    supportAddress: settings["outgoingEmails.supportAddress"],
                },
                emailFormat: settings["emailStyles.format"] == true ? "html" : "text",
                templateStyles: {
                    logoUrl: settings["emailStyles.image"],
                    textColor: settings["emailStyles.textColor"],
                    backgroundColor: settings["emailStyles.backgroundColor"],
                    containerBackgroundColor: settings["emailStyles.containerBackgroundColor"],
                    buttonTextColor: settings["emailStyles.buttonTextColor"],
                    buttonBackgroundColor: settings["emailStyles.buttonBackgroundColor"],
                },
                footer: JSON.stringify(settings["outgoingEmails.footer"]),
            });
        },
        onSuccess: () => {
            onCancel();
            toast.addToast({
                dismissible: true,
                body: <>{t("The email has been sent.")}</>,
            });
        },
    });

    return (
        <>
            <Modal
                isVisible={true}
                size={ModalSizes.LARGE}
                exitHandler={() => {
                    onCancel();
                }}
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        testEmailMutation.mutate();
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                closeFrame={() => {
                                    onCancel();
                                }}
                                title={t("Send Test Email Digest")}
                            />
                        }
                        body={
                            <FrameBody>
                                {testEmailMutation.error && (
                                    <Message
                                        className={
                                            (accountSettingsClasses().topLevelErrors,
                                            dashboardAddEditUserClasses().topLevelError)
                                        }
                                        type={"error"}
                                        stringContents={testEmailMutation.error.message}
                                    />
                                )}
                                <DashboardFormGroup
                                    label={t("Community Member Content")}
                                    description={t(
                                        "The test digest will generate content as if it were this user receiving the digest.",
                                    )}
                                >
                                    <div className="input-wrap">
                                        <CommunityMemberInput
                                            label={null}
                                            placeholder={t("Search Members")}
                                            value={[]}
                                            onChange={(value) => {
                                                if (!value) {
                                                    return;
                                                }
                                                setDestinationUserID(value[0].data.userID);
                                            }}
                                        />
                                        {testEmailMutation.error?.errors?.destinationUserID && (
                                            <ErrorMessages
                                                errors={testEmailMutation.error?.errors?.destinationUserID}
                                            />
                                        )}
                                    </div>
                                </DashboardFormGroup>
                                <DashboardFormGroup
                                    label={t("Recipient")}
                                    description={t("The email address this test will be sent to.")}
                                >
                                    <DashboardInput
                                        inputProps={{
                                            onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                                setDestinationAddress(event.target.value);
                                            },
                                        }}
                                        errors={testEmailMutation.error?.errors?.destinationAddress}
                                    />
                                </DashboardFormGroup>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        onCancel();
                                    }}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button submit buttonType={ButtonTypes.TEXT_PRIMARY}>
                                    {testEmailMutation.isLoading ? <ButtonLoader /> : t("Send")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        </>
    );
}
