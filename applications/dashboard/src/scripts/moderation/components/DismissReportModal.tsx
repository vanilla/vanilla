/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDismissReport } from "@dashboard/moderation/CommunityManagementTypes";
import { css } from "@emotion/css";
import apiv2 from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import CheckBox from "@library/forms/Checkbox";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useState } from "react";

interface IProps {
    reportIDs: number | number[];
    isVisible: boolean;
    onClose: () => void;
}

const classes = {
    message: css({
        marginBottom: 16,
    }),
};

export function DismissReportModal(props: IProps) {
    const [verifyUser, setVerifyUser] = useState(false);
    const toast = useToast();
    const queryClient = useQueryClient();
    const mutation = useMutation({
        mutationFn: async (params: IDismissReport) => {
            return await apiv2.post("/reports/dismiss", params);
        },
    });
    return (
        <Modal isVisible={props.isVisible} exitHandler={props.onClose} size={ModalSizes.SMALL}>
            <Frame
                header={<FrameHeader title={t("Dismiss Report")} closeFrame={props.onClose} />}
                body={
                    <FrameBody hasVerticalPadding>
                        {Array.isArray(props.reportIDs) && (
                            <Message
                                className={classes.message}
                                type={"info"}
                                stringContents={t("All the reports associated with this post will be dismissed.")}
                            />
                        )}
                        <p>{t("Verifying a user will cause them to bypass premoderation in the future.")}</p>
                        <CheckBox
                            label={t("Verify User")}
                            checked={verifyUser}
                            onChange={(e) => setVerifyUser(e.target.checked)}
                        />
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button onClick={props.onClose} buttonType={ButtonTypes.TEXT}>
                            {t("Cancel")}
                        </Button>
                        <Button
                            onClick={() => {
                                // This should probably be an API
                                [...[props.reportIDs]].flat().forEach((reportID, counter) => {
                                    mutation.mutateAsync({ reportID, verifyRecordUser: verifyUser }).then(() => {
                                        if (counter === [...[props.reportIDs]].flat().length - 1) {
                                            queryClient.invalidateQueries(["reports"]);
                                            queryClient.invalidateQueries(["triageItem"]);
                                            toast.addToast({
                                                autoDismiss: true,
                                                body: t("Report dismissed"),
                                            });
                                            props.onClose();
                                        }
                                    });
                                });
                            }}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                        >
                            {mutation.isLoading ? <ButtonLoader /> : t("Dismiss Report")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}
