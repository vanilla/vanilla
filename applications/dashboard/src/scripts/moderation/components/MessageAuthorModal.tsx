/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import { useUser } from "@library/features/users/userHooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { MyValue } from "@library/vanilla-editor/typescript";
import { useMutation } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import React from "react";

export interface IMessageInfo {
    userID: number;
    url: string;
}

interface IProps {
    messageInfo: IMessageInfo | null;
    isVisible: boolean;
    onClose: () => void;
}

export function MessageAuthorModal(props: IProps) {
    const { isVisible, onClose, messageInfo } = props;

    const [message, setMessage] = React.useState<MyValue | undefined>();

    const toast = useToast();
    // Assuming conversations here
    const messageMutation = useMutation({
        mutationFn: async () => {
            const response = await apiv2.post("/conversations", {
                participantUserIDs: [messageInfo?.userID],
                initialBody: JSON.stringify(message),
                initialFormat: "rich2",
            });
            return response.data;
        },
        onSuccess() {
            toast.addToast({
                autoDismiss: true,
                body: t("Message Sent"),
            });
            handleClose();
        },
    });

    const handleClose = () => {
        setMessage(undefined);
        onClose();
    };

    return (
        <Modal isVisible={isVisible} exitHandler={handleClose} size={ModalSizes.MEDIUM}>
            <Frame
                header={<FrameHeader title={t("Message Post Author")} closeFrame={handleClose} />}
                body={
                    <FrameBody hasVerticalPadding>
                        {isVisible && (
                            <MessageAuthorEditor
                                userID={messageInfo?.userID}
                                url={messageInfo?.url}
                                onChange={setMessage}
                            />
                        )}
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button onClick={handleClose} buttonType={ButtonTypes.TEXT}>
                            {t("Cancel")}
                        </Button>
                        <Button
                            onClick={() => {
                                messageMutation.mutate();
                            }}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                        >
                            {messageMutation.isLoading ? <ButtonLoader /> : t("Send")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}

function MessageAuthorEditor(props: { userID?: number; url?: string; onChange: (value: MyValue) => void }) {
    const { onChange } = props;

    const user = useUser({ userID: props.userID ?? -1 });

    const initialMessage: MyValue = [
        {
            type: "p",
            children: [
                {
                    text: `Hello ${user.data?.name ?? "member"},\n\nThis is regarding your post: ${props.url}`,
                },
            ],
        },
    ];
    return <VanillaEditor onChange={onChange} initialContent={initialMessage} />;
}
