/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useEffect, useState } from "react";
import ModalSizes from "@library/modal/ModalSizes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IError } from "@library/errorPages/CoreErrorMessages";
import Modal from "@library/modal/Modal";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { css, cx } from "@emotion/css";
import FrameFooter from "@library/layout/frame/FrameFooter";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import { draftsClasses } from "@vanilla/addon-vanilla/drafts/Drafts.classes";
import InputBlock from "@library/forms/InputBlock";
import DatePicker from "@library/forms/DatePicker";
import { useStackingContext } from "@vanilla/react-utils";
import { TextInput } from "@library/forms/TextInput";
import moment from "moment";
import { useToast } from "@library/features/toaster/ToastContext";
import Translate from "@library/content/Translate";
import { DraftRecordType, DraftStatus, IDraft } from "@vanilla/addon-vanilla/drafts/types";
import DateTime, { DateFormats } from "@library/content/DateTime";
import { useScheduleDraftMutation } from "@vanilla/addon-vanilla/drafts/Draft.hooks";
import ButtonLoader from "@library/loaders/ButtonLoader";
import SmartLink from "@library/routing/links/SmartLink";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";

export interface IDraftModalProps {
    draftID?: IDraft["draftID"];
    recordType: IDraft["recordType"];
    isVisible: boolean;
    onSubmit?: (values: {
        draftID?: IDraft["draftID"];
        dateScheduled?: string;
        draftStatus: DraftStatus;
    }) => Promise<void>;
    onVisibilityChange: (visible: boolean) => void;
    error?: IError;
    onCancel?: () => void;
    isDraftForExistingRecord?: boolean;
    isScheduledDraft?: boolean;
}

interface IProps extends IDraftModalProps {
    initialSchedule?: string;
}

interface IStateValues {
    scheduledDate?: {
        date?: string;
        time?: string;
    };
}

export function DraftScheduleModal(props: IProps) {
    const { initialSchedule, recordType, draftID, isDraftForExistingRecord, isScheduledDraft } = props;
    const classes = draftsClasses();
    const { zIndex } = useStackingContext();
    const toast = useToast();

    const { pushSmartLocation } = useLinkContext();

    const modalTitle =
        recordType === DraftRecordType.ARTICLE
            ? "Schedule Article"
            : recordType === DraftRecordType.EVENT
            ? "Schedule Event"
            : "Schedule Post";

    const [values, setValues] = useState<IStateValues>({});

    function setValuesFromProps(schedule?: string) {
        setValues(
            schedule
                ? {
                      scheduledDate: {
                          date: moment(initialSchedule).format("YYYY-MM-DD"),
                          time: moment(initialSchedule).format("HH:mm"),
                      },
                  }
                : {},
        );
    }
    useEffect(() => {
        setValuesFromProps(initialSchedule);
    }, []);

    useEffect(() => {
        setValuesFromProps(initialSchedule);
    }, [initialSchedule]);

    const [error, setError] = useState<IError>();

    const [isSubmitting, setIsSubmitting] = useState(false);

    const scheduleDraftMutation = useScheduleDraftMutation();

    const onClose = async () => {
        props.onCancel?.();
        props.onVisibilityChange(false);
        setError(undefined);
    };

    const handleSubmit = async () => {
        try {
            setIsSubmitting(true);

            const dateUTC = moment(values.scheduledDate?.date, "YYYY-MM-DD")
                .set({
                    hour: moment(values.scheduledDate?.time, "HH:mm").get("hour"),
                    minute: moment(values.scheduledDate?.time, "HH:mm").get("minute"),
                })
                .toISOString();

            if (props.onSubmit) {
                setError(undefined);
                await props.onSubmit({ draftID, dateScheduled: dateUTC, draftStatus: DraftStatus.SCHEDULED });
            } else {
                await scheduleDraftMutation.mutateAsync({ draftID: draftID!, dateScheduled: dateUTC });
            }
            if (recordType === DraftRecordType.ARTICLE) {
                toast.setIsInModal(true);
            }
            isScheduledDraft &&
                toast.addToast({
                    autoDismiss: true,
                    autoDismissDuration: 5000,
                    body: (
                        <>
                            <Translate
                                source={"Your <0/> will be published on <1/>."}
                                c0={recordType === DraftRecordType.DISCUSSION ? "post" : recordType}
                                c1={<DateTime timestamp={dateUTC} type={DateFormats.EXTENDED} />}
                            />
                            <br />
                            <Translate
                                source={"Edit it in <0/>."}
                                c0={<SmartLink to={"/drafts?tab=schedule"}>{t("Scheduled Content")}</SmartLink>}
                            />
                        </>
                    ),
                });
            props.onVisibilityChange(false);
            setError(undefined);
            !isScheduledDraft && pushSmartLocation("/drafts?tab=schedule");
        } catch (error) {
            const fieldError = error.errors && Object.values(error.errors ?? {})[0];
            const errorMessage = fieldError
                ? fieldError.message ??
                  ((fieldError ?? []).length && fieldError[0]["message"] && fieldError[0]["message"])
                : error.message;

            const alreadyScheduledBySameUser = fieldError?.status?.isOwnSchedule || error.response?.data?.isOwnSchedule;

            const withLinkToScheduledPosts =
                alreadyScheduledBySameUser &&
                ((fieldError?.status?.recordID && fieldError?.status?.draftID) ||
                    (error.response?.data?.draftID && error.response?.data?.recordID));

            setError({
                message: errorMessage,
                ...(withLinkToScheduledPosts && {
                    actionButton: {
                        url: "/drafts?tab=schedule",
                        label: t("Scheduled Content"),
                    },
                }),
            });
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Modal
            isVisible={props.isVisible}
            exitHandler={onClose}
            size={ModalSizes.SMALL}
            titleID={"schedule-draft-modal"}
        >
            <form
                role="form"
                onSubmit={async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    await handleSubmit();
                }}
            >
                <Frame
                    header={
                        <FrameHeader
                            titleID={"schedule-draft-modal"}
                            closeFrame={onClose}
                            title={`${t(modalTitle)} ${isDraftForExistingRecord ? t("Update") : ""}`}
                        />
                    }
                    body={
                        <FrameBody>
                            {!!(props.error || error) && (
                                <div className={classes.verticalGap}>
                                    <Message
                                        type="error"
                                        stringContents={props.error ? props.error.message : error!.message}
                                        icon={<ErrorIcon />}
                                        error={(props.error ?? error) as IError}
                                        className={classes.scheduleModalErrorMessage}
                                    />
                                </div>
                            )}
                            <div className={cx(frameBodyClasses().contents)}>
                                <InputBlock label={t("Date")} required>
                                    <DatePicker
                                        required
                                        alignment="right"
                                        onChange={(newDate) => {
                                            setValues({
                                                scheduledDate: {
                                                    ...values.scheduledDate,
                                                    date: newDate ?? "",
                                                },
                                            });
                                        }}
                                        value={values.scheduledDate?.date ?? ""}
                                        datePickerDropdownClassName={css({
                                            zIndex: zIndex + 1,
                                        })}
                                    />
                                </InputBlock>
                                <InputBlock label={t("Time")} required>
                                    <TextInput
                                        required
                                        value={values.scheduledDate?.time}
                                        type="time"
                                        onChange={(e) => {
                                            setValues({
                                                scheduledDate: {
                                                    ...values.scheduledDate,
                                                    time: e.target.value,
                                                },
                                            });
                                        }}
                                    />
                                </InputBlock>
                            </div>
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight>
                            <Button buttonType={ButtonTypes.TEXT} disabled={isSubmitting} onClick={onClose}>
                                {t("Cancel")}
                            </Button>
                            <Button buttonType={ButtonTypes.TEXT_PRIMARY} disabled={isSubmitting} submit>
                                {isSubmitting ? <ButtonLoader /> : t("Schedule")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}
