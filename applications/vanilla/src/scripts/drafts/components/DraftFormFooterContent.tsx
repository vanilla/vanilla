/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import DateTime, { DateFormats } from "@library/content/DateTime";
import { DraftStatus, IDraft } from "@vanilla/addon-vanilla/drafts/types";

import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { CancelDraftScheduleModal } from "@vanilla/addon-vanilla/drafts/components/CancelDraftScheduleModal";
import ConfirmPostingScheduledDraft from "./ConfirmPostingScheduledDraft";
import { DraftScheduleModal } from "@vanilla/addon-vanilla/drafts/components/DraftScheduleModal";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import PublishNotificationModal, { NotificationOption } from "./PublishNotificationModal";
import SmartLink from "@library/routing/links/SmartLink";
import Translate from "@library/content/Translate";
import { draftFormFooterContentClasses } from "@vanilla/addon-vanilla/drafts/components/DraftFormFooterContent.classes";
import { t } from "@vanilla/i18n";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { useState } from "react";
import { useToast } from "@library/features/toaster/ToastContext";
import { isPostDraftMeta } from "@vanilla/addon-vanilla/drafts/utils";

export default function DraftFormFooterContent(props: {
    draftSchedulingEnabled?: boolean;
    onOpenScheduleModal?: () => void;
    handleSaveDraft: (draftParams?: Partial<DraftsApi.PatchParams>) => Promise<void>;
    disabled?: boolean;
    formRef: React.RefObject<HTMLFormElement>;
    isDraftForExistingRecord: boolean;
    isScheduledDraft: boolean;
    recordType: string;
    submitDisabled?: boolean;
    canPostSilently?: boolean;
    handleSubmit: (silentPublish?: boolean) => Promise<void>;
}) {
    const classes = draftFormFooterContentClasses();
    const { draftID, draft, draftLastSavedToServer } = useDraftContext();

    const {
        draftSchedulingEnabled = false,
        onOpenScheduleModal,
        disabled,
        formRef,
        handleSaveDraft,
        recordType,
        submitDisabled = false,
        canPostSilently = false,
        handleSubmit,
    } = props;
    const [ownDisabled, setOwnDisabled] = useState(false);

    const toast = useToast();

    const isScheduledDraft = !!draft && !!draft.dateScheduled;
    const scheduledDate = isScheduledDraft && draft.dateScheduled;
    const isErroredSchedule = isScheduledDraft && (draft as IDraft).draftStatus === DraftStatus.ERROR;

    const [isScheduleModalVisible, setIsScheduleModalVisible] = useState(false);
    const [isCancelScheduleModalVisible, setIsCancelScheduleModalVisible] = useState(false);
    const [isPublishNotificationModalVisible, setIsPublishNotificationModalVisible] = useState(false);

    const draftMeta = draft?.attributes?.draftMeta ?? {};
    const notificationOption = isPostDraftMeta(draftMeta) && !!draftMeta?.publishedSilently;

    async function saveDraft(draftParams?: Partial<DraftsApi.PatchParams>) {
        setOwnDisabled(true);
        await handleSaveDraft(draftParams);
        setOwnDisabled(false);
    }

    const handleScheduleButton = async () => {
        const showScheduleModal = () => {
            setIsScheduleModalVisible(true);
            onOpenScheduleModal?.();
        };
        const isValidForm = formRef.current?.checkValidity();
        if (isValidForm) {
            if (draftID) {
                showScheduleModal();
            } else {
                // first save draft and then show schedule modal
                await saveDraft();
                showScheduleModal();
            }
        } else {
            formRef.current?.reportValidity();
        }
    };

    const [postScheduledDraftModalVisible, setPostScheduledDraftModalVisible] = useState(false);

    const saveDraftButton = (
        <Button
            onClick={async () => {
                await saveDraft();
                toast.addToast({
                    autoDismiss: true,
                    autoDismissDuration: 5000,
                    body: (
                        <>
                            <Translate
                                source={
                                    isScheduledDraft
                                        ? "Changes Saved! Your <0/> is still scheduled for <1/>."
                                        : "Your <0/> has been saved and can be edited anytime in your <1/>."
                                }
                                c0={recordType === "discussion" ? t("post") : t(recordType)}
                                c1={
                                    isScheduledDraft && draft.dateScheduled ? (
                                        <DateTime date={draft.dateScheduled} type={DateFormats.EXTENDED} />
                                    ) : (
                                        <SmartLink to={"/drafts"}>{t("Drafts")}</SmartLink>
                                    )
                                }
                            />
                        </>
                    ),
                });
            }}
            buttonType={isScheduledDraft ? ButtonTypes.PRIMARY : ButtonTypes.OUTLINE}
            disabled={disabled || ownDisabled}
        >
            {isScheduledDraft ? t("Save Changes") : t("Save as Draft")}
        </Button>
    );

    const buttonProps = {
        onClick: (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (!isScheduledDraft && !canPostSilently) {
                formRef.current?.requestSubmit();
            }
            if (isScheduledDraft) {
                setPostScheduledDraftModalVisible(true);
            }
            if (!isScheduledDraft && canPostSilently) {
                setIsPublishNotificationModalVisible(true);
            }
        },
        disabled: submitDisabled,
        buttonType: isScheduledDraft ? ButtonTypes.OUTLINE : ButtonTypes.PRIMARY,
        submit: true,
    };

    const postButton = (
        <Button {...buttonProps}>
            {submitDisabled ? <ButtonLoader /> : isScheduledDraft ? t("Post Now") : t("Post")}
        </Button>
    );

    return (
        <>
            <div className={classes.footer}>
                {!draftSchedulingEnabled && saveDraftButton}
                {draftSchedulingEnabled && (
                    <>
                        {!isScheduledDraft && saveDraftButton}
                        {isScheduledDraft && !isErroredSchedule && (
                            <Button
                                disabled={disabled || ownDisabled}
                                onClick={() => setIsCancelScheduleModalVisible(true)}
                                buttonType={ButtonTypes.OUTLINE}
                            >
                                {t("Cancel Schedule")}
                            </Button>
                        )}

                        <Button
                            disabled={disabled || ownDisabled}
                            onClick={handleScheduleButton}
                            buttonType={ButtonTypes.OUTLINE}
                        >
                            {isScheduledDraft && !isErroredSchedule ? t("Edit Schedule") : t("Schedule")}
                        </Button>
                        {isScheduledDraft && (
                            <>
                                {postButton}
                                {saveDraftButton}
                            </>
                        )}
                        {draftID && draft && (
                            <>
                                <DraftScheduleModal
                                    draftID={draftID}
                                    recordType={draft.recordType}
                                    isVisible={isScheduleModalVisible}
                                    onVisibilityChange={setIsScheduleModalVisible}
                                    initialSchedule={!isErroredSchedule ? (draft as IDraft).dateScheduled : undefined}
                                    onSubmit={async (values) => {
                                        await saveDraft(values);
                                    }}
                                    onCancel={() => setOwnDisabled(false)}
                                    isDraftForExistingRecord={props.isDraftForExistingRecord}
                                    isScheduledDraft={props.isScheduledDraft}
                                    notificationOption={canPostSilently ? notificationOption : null}
                                    canPostSilently={canPostSilently}
                                />
                                <CancelDraftScheduleModal
                                    draftID={draftID}
                                    recordType={draft.recordType}
                                    isVisible={isCancelScheduleModalVisible}
                                    onVisibilityChange={setIsCancelScheduleModalVisible}
                                    onSubmit={async (values) => {
                                        await saveDraft(values);
                                    }}
                                    isDraftForExistingRecord={props.isDraftForExistingRecord}
                                />
                            </>
                        )}
                        <ConfirmPostingScheduledDraft
                            isVisible={postScheduledDraftModalVisible}
                            onClose={() => {
                                setPostScheduledDraftModalVisible(false);
                            }}
                            onConfirm={async () => {
                                if (canPostSilently) {
                                    setIsPublishNotificationModalVisible(true);
                                } else {
                                    await handleSubmit(false);
                                }
                                setPostScheduledDraftModalVisible(false);
                            }}
                            scheduledDate={draft?.dateScheduled}
                        />
                    </>
                )}
                {!isScheduledDraft && <>{postButton}</>}
            </div>
            <div>
                {draftLastSavedToServer && !isScheduledDraft && (
                    <span className={classes.draftLastSaved}>
                        {t("Draft last saved: ")}
                        <DateTime mode={"relative"} timestamp={draftLastSavedToServer} />
                    </span>
                )}
                {scheduledDate && !isErroredSchedule && (
                    <span className={classes.draftLastSaved}>
                        {t("Scheduled for: ")}
                        <DateTime timestamp={scheduledDate} type={DateFormats.EXTENDED} />
                    </span>
                )}
            </div>
            <PublishNotificationModal
                isVisible={isPublishNotificationModalVisible}
                onClose={() => setIsPublishNotificationModalVisible(false)}
                onConfirm={async (notificationOption: NotificationOption) => {
                    notificationOption === "notify" ? await handleSubmit(false) : await handleSubmit(true);
                    setIsPublishNotificationModalVisible(false);
                }}
                submitDisabled={submitDisabled}
            />
        </>
    );
}
