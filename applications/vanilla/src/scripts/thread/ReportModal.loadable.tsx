/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import Translate from "@library/content/Translate";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import { IPermission } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import Button from "@library/forms/Button";
import Checkbox from "@library/forms/Checkbox";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Heading from "@library/layout/Heading";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { MyValue } from "@library/vanilla-editor/typescript";
import { useMutation, useQuery } from "@tanstack/react-query";
import { IReportRecordProps } from "@vanilla/addon-vanilla/thread/ThreadItemActions";
import { reportModalClasses } from "@vanilla/addon-vanilla/thread/reportModal.classes";
import { t } from "@vanilla/i18n";
import { RecordID, logError, notEmpty } from "@vanilla/utils";
import isEqual from "lodash-es/isEqual";
import { useState } from "react";
import { useDiscussionThreadContext } from "@vanilla/addon-vanilla/thread/DiscussionThreadContext";
import { formatUrl } from "@library/utility/appUtils";

interface IProps {
    discussionName: string;
    recordID: IReportRecordProps["recordID"];
    recordType: IReportRecordProps["recordType"];
    isVisible: boolean;
    onVisibilityChange: (visible: boolean) => void;
    onSuccess?: () => Promise<void>;
    isLegacyPage?: boolean;
}

interface IReportReason {
    reportReasonID: string;
    name: string;
    description: string;
    sort: number;
    dateInserted: Date | string;
    dateUpdated: Date | string;
    insertUserID: RecordID;
    updateUserID: RecordID;
    permission: IPermission | null;
}

const EMPTY_RICH2: MyValue = [
    {
        children: [{ text: "" }],
        type: "p",
    },
];

/**
 * @deprecated Do not import this component, import ReportModal instead
 */
export default function ReportModalLoadable(props: IProps) {
    const { isVisible, onVisibilityChange, recordID, recordType, isLegacyPage } = props;
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const classes = reportModalClasses();
    const { addToast } = useToast();
    const { hasPermission } = usePermissionsContext();
    const { discussion: isNewDiscussionThreadPage } = useDiscussionThreadContext();

    // Moderators have extra options in this modal
    const isModerator = hasPermission("community.manage");

    const [confirmDialogVisible, setConfirmDialogVisible] = useState(false);
    const [escalateImmediately, setEscalateImmediately] = useState(false);
    const [hideFromCommunity, setHideFromCommunity] = useState(false);
    const [error, setError] = useState<IError>();
    const [reasons, setReasons] = useState<string[]>([]);
    const [notes, setNotes] = useState<MyValue>(EMPTY_RICH2);

    // Get report reasons
    const reportReasons = useQuery<any, IError, IReportReason[]>({
        queryFn: async () => {
            const response = await apiv2.get(`/report-reasons`);
            return response.data;
        },
        queryKey: ["reportReasons"],
    });

    const hasReasonOptions = reportReasons.isSuccess;

    const reportRecord = useMutation({
        mutationFn: async () => {
            const reportPayload = {
                recordType,
                recordID,
                reportReasonIDs: reasons,
                noteBody: JSON.stringify(notes),
                noteFormat: "rich2",
            };

            const escalationPayload = {
                name: `${recordType === "comment" ? "Re: " : ""}${props.discussionName}`,
                recordIsLive: !hideFromCommunity,
                status: "open",
            };

            const apiEndpoint = escalateImmediately ? `/escalations` : `/reports`;

            const response = await apiv2.post(apiEndpoint, {
                ...reportPayload,
                ...(escalateImmediately && { ...escalationPayload }),
            });
            return response.data;
        },
        mutationKey: [recordType, recordID, reasons, notes],
        onSuccess() {
            addToast({
                autoDismiss: true,
                body: (
                    <>
                        <Translate
                            source={"Success! Your <0/> has been submitted."}
                            c0={escalateImmediately ? "escalation" : "report"}
                        />
                    </>
                ),
            });
            resetAndExit();
            props.onSuccess?.();
            hideFromCommunity && handleHideFromCommunity(isLegacyPage, recordType);
        },
        onError(error: IError) {
            logError(error);
            setError(error);
            addToast({
                autoDismiss: false,
                dismissible: true,
                body: <>{error.message}</>,
            });
        },
    });

    const submitForm = async () => {
        if (reasons.length === 0) {
            setError({ message: t("Please select a reason for reporting this content.") });
            return;
        } else {
            setError(undefined);
            await reportRecord.mutateAsync();
        }
    };

    const resetAndExit = () => {
        setConfirmDialogVisible(false);
        onVisibilityChange(false);
        setReasons([]);
        setNotes(EMPTY_RICH2);
        setError(undefined);
        setEscalateImmediately(false);
        setHideFromCommunity(false);
        reportRecord.reset();
    };

    const handleCloseButton = () => {
        if (reasons.length > 0 || !isEqual(notes, EMPTY_RICH2)) {
            setConfirmDialogVisible(true);
        } else {
            resetAndExit();
        }
    };

    const handleHideFromCommunity = (isLegacyPage: boolean = false, recordType) => {
        // if we are on a discussion page, let's redirect to discussion list if we are removing the discussion itself
        if (recordType === "discussion") {
            if (isNewDiscussionThreadPage || isLegacyPage) {
                window.location.href = formatUrl(`/discussions`);
            }
        } else if (isLegacyPage) {
            // if we are on a legacy page, removing a comment, let's refresh the page
            window.location.reload();
        }
    };

    const reasonCheckbox = (reason: IReportReason) => {
        return (
            <span key={reason.reportReasonID}>
                <Checkbox
                    className={classes.checkbox}
                    label={
                        <div className={classes.reasonLayout}>
                            <span className={"name"}>{reason.name}</span>
                            <span className={"description"}>{reason.description}</span>
                        </div>
                    }
                    labelBold={false}
                    onChange={() => {
                        setReasons((prev) => {
                            if (prev.includes(reason.reportReasonID)) {
                                return prev.filter((r) => r !== reason.reportReasonID);
                            }
                            return [...reasons, reason.reportReasonID];
                        });
                    }}
                    checked={reasons.includes(reason.reportReasonID)}
                />
            </span>
        );
    };

    return (
        <>
            <Modal
                isVisible={isVisible}
                exitHandler={() => handleCloseButton()}
                size={ModalSizes.MEDIUM}
                titleID={"reportRecord"}
            >
                <form
                    onSubmit={async (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        await submitForm();
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                titleID={"reportThisPost"}
                                closeFrame={() => resetAndExit()}
                                title={t("Report Post")}
                            />
                        }
                        body={
                            <FrameBody className={classesFrameBody.root}>
                                <div className={classes.layout}>
                                    <>
                                        <ErrorMessages errors={[error].filter(notEmpty)} />
                                        <Heading className={classes.formHeadings} depth={3}>
                                            {t("Reason")}
                                        </Heading>
                                        <div className={classes.scrollableArea}>
                                            {hasReasonOptions &&
                                                reportReasons.data.map((reportReason) => reasonCheckbox(reportReason))}
                                        </div>
                                        <Heading className={classes.formHeadings} depth={3}>
                                            {t("Additional Details")}
                                        </Heading>
                                        <VanillaEditor
                                            containerClasses={classes.editorClasses}
                                            onChange={setNotes}
                                            initialContent={notes}
                                        />
                                        {isModerator && (
                                            <>
                                                <Heading className={classes.formHeadings} depth={3}>
                                                    {t("Moderation")}
                                                </Heading>
                                                <Checkbox
                                                    className={classes.moderationOptions}
                                                    label={t("Escalate immediately")}
                                                    labelBold={false}
                                                    onChange={(event) => {
                                                        if (hideFromCommunity && !event.target.checked) {
                                                            setHideFromCommunity(false);
                                                        }
                                                        setEscalateImmediately(event.target.checked);
                                                    }}
                                                    checked={escalateImmediately}
                                                />
                                                <Checkbox
                                                    className={classes.moderationOptions}
                                                    label={t("Remove from front-facing community")}
                                                    labelBold={false}
                                                    onChange={(event) => {
                                                        if (!escalateImmediately) {
                                                            setEscalateImmediately(event.target.checked);
                                                        }
                                                        setHideFromCommunity(event.target.checked);
                                                    }}
                                                    checked={hideFromCommunity}
                                                />
                                            </>
                                        )}
                                    </>
                                </div>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <>
                                    <Button
                                        buttonType={ButtonTypes.TEXT}
                                        onClick={() => handleCloseButton()}
                                        className={classFrameFooter.actionButton}
                                    >
                                        {t("Cancel")}
                                    </Button>
                                    <Button
                                        submit
                                        disabled={reportRecord.isLoading}
                                        buttonType={ButtonTypes.TEXT_PRIMARY}
                                    >
                                        {reportRecord.isLoading ? (
                                            <ButtonLoader />
                                        ) : escalateImmediately ? (
                                            t("Create Escalation")
                                        ) : (
                                            t("Send Report")
                                        )}
                                    </Button>
                                </>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>

            <ModalConfirm
                isVisible={confirmDialogVisible}
                title={t("Discard Report?")}
                onCancel={() => setConfirmDialogVisible(false)}
                onConfirm={() => resetAndExit()}
                confirmTitle={t("Discard")}
            >
                {t("Are you sure you want to exit without reporting?")}
            </ModalConfirm>
        </>
    );
}
