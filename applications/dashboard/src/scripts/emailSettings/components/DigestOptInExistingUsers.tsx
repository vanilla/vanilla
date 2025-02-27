/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import Message from "@library/messages/Message";
import { InformationIcon } from "@library/icons/common";
import Button from "@library/forms/Button";
import { t } from "@vanilla/i18n";
import ModalSizes from "@library/modal/ModalSizes";
import Modal from "@library/modal/Modal";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { useRef, useState } from "react";
import { useFormik } from "formik";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import apiv2 from "@library/apiv2";
import { IJsonSchemaFormHandle } from "@vanilla/json-schema-forms";
import digestOptInExistingUsersStyles from "./DigestOptInExistingUsers.styles";
import { useToast } from "@library/features/toaster/ToastContext";
import { useConfigMutation } from "@library/config/configHooks";

function calculateDate(timeFrame: string) {
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth();
    const currentDay = currentDate.getDate();

    let year = currentYear;
    let month = currentMonth;
    let day = currentDay;

    switch (timeFrame) {
        case "6months":
            month -= 6;
            break;
        case "1year":
            year -= 1;
            break;
        case "2years":
            year -= 2;
            break;
        case "3years":
            year -= 3;
            break;
        case "4years":
            year -= 4;
            break;
        case "5years":
            year -= 5;
            break;
        default:
            break;
    }

    return new Date(year, month, day).toISOString().split("T")[0];
}

export default function DigestAutoSubscribe() {
    const [modalVisible, setModalVisible] = useState(false);
    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const patchConfig = useConfigMutation();

    const toast = useToast();
    const { values, setValues, submitForm, resetForm, isSubmitting } = useFormik({
        initialValues: {
            timeFrame: "1year",
        },
        onSubmit: async (values) => {
            try {
                await patchConfig.mutateAsync({
                    "emailDigest.enabled": true,
                    "emailDigest.autosubscribe.enabled": true,
                });

                await apiv2.post("/digest/backfill-optin", { dateLastActive: calculateDate(values.timeFrame) });
                toast.addToast({
                    autoDismiss: true,
                    dismissible: true,
                    body: <>{t("Opt-in Success! Backdated existing user subscriptions.")}</>,
                });
            } catch (e) {
                console.error("Error backfilling autosubscribe", e);
                toast.addToast({
                    autoDismiss: false,
                    dismissible: true,
                    body: <>{t("Opt-in Error. Unable to backdate existing user subscriptions.")}</>,
                });
            }

            resetForm();
            setModalVisible(false);
        },
    });

    const TIME_FRAME_SCHEMA = {
        type: "object",
        properties: {
            timeFrame: {
                type: "string",
                default: "1year",
                "x-control": {
                    label: t("Opt-in Backdate"),
                    description: (
                        <>
                            {t("Set a backdate based on last log in and opt-in those users to receiving the Digest.")}
                            <br />
                            <a href="https://www.higherlogic.com/gdpr/" target="_blank">
                                {t("More information")}
                            </a>
                        </>
                    ),
                    inputType: "select",
                    isClearable: false,
                    options: [
                        { value: "6months", label: t("6 months") },
                        { value: "1year", label: t("1 year") },
                        { value: "2years", label: t("2 years") },
                        { value: "3years", label: t("3 years") },
                        { value: "4years", label: t("4 years") },
                        { value: "5years", label: t("5 years") },
                    ],
                },
            },
        },
    };

    return (
        <>
            <Modal size={ModalSizes.LARGE} isVisible={modalVisible} exitHandler={() => setModalVisible(false)}>
                <form
                    className={digestOptInExistingUsersStyles().form}
                    role="form"
                    onSubmit={async (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        await submitForm();
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                closeFrame={() => setModalVisible(false)}
                                title={t("Opt-in Existing Users to Digest")}
                            />
                        }
                        body={
                            <FrameBody hasVerticalPadding>
                                <Message
                                    icon={<InformationIcon />}
                                    stringContents={t(
                                        "Note: These users will be provided unsubscribe options when they receive their digests. Be sure to check local laws to ensure GDPR compliance.",
                                    )}
                                    type="neutral"
                                />
                                <DashboardSchemaForm
                                    disabled={isSubmitting}
                                    schema={TIME_FRAME_SCHEMA}
                                    instance={values}
                                    ref={schemaFormRef}
                                    onChange={setValues}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <Button buttonType={ButtonTypes.TEXT} onClick={() => setModalVisible(false)}>
                                    {t("Cancel")}
                                </Button>

                                <Button type="submit" buttonType={ButtonTypes.TEXT_PRIMARY}>
                                    {t("Run")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>

            <Button onClick={() => setModalVisible(!modalVisible)}>{t("Backdate")}</Button>
        </>
    );
}
