/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import {
    EscalationStatus,
    IEscalation,
    IPostEscalation,
    IReport,
} from "@dashboard/moderation/CommunityManagementTypes";
import { IUser } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { FormControlGroup, FormControlWithNewDropdown } from "@library/forms/FormControl";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useMutation } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { JSONSchemaType, JsonSchemaForm, PartialSchemaDefinition } from "@vanilla/json-schema-forms";
import { useEffect, useMemo, useState } from "react";
import { labelize } from "@vanilla/utils";
import { IApiError } from "@library/@types/api/core";
import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";

interface IProps {
    escalationType: "report" | "record";
    recordType?: string;
    record?: IDiscussion | IComment | null;
    report?: IReport | null;
    isVisible: boolean;
    onClose: () => void;
}

interface EscalateForm {
    name: IPostEscalation["name"];
    status: IPostEscalation["status"]; // some enum here
    assignee?: IUser["userID"];
    initialCommentBody: IPostEscalation["initialCommentBody"];
    initialCommentFormat: IPostEscalation["initialCommentFormat"];
    removePost: boolean;
    removeMethod: IPostEscalation["removeMethod"];
    reportReasonIDs?: string[];
}

// This probably belongs in the BE
const ESCALATE_SCHEMA: JSONSchemaType<EscalateForm> = {
    type: "object",
    properties: {
        name: {
            type: "string",
            default: "",
            "x-control": {
                label: t("Escalation Name"),
                inputType: "textBox",
            },
        },
        status: {
            type: "string",
            default: "open",
            "x-control": {
                label: t("Status on Creation"),
                inputType: "dropDown",
                choices: {
                    staticOptions: Object.fromEntries(
                        Object.values(EscalationStatus).map((status) => [status, labelize(status)]),
                    ),
                },
            },
        },
        assignee: {
            type: "string",
            default: "",
            "x-control": {
                label: t("Assignee"),
                inputType: "dropDown",
                choices: {
                    api: {
                        searchUrl: "/api/v2/users/by-names?name=%s*&limit=10",
                        labelKey: "name",
                        valueKey: "userID",
                        singleUrl: "/api/v2/users/%s",
                    },
                },
            },
        },
        initialCommentBody: {
            type: "string",
            default: "",
            "x-control": {
                label: t("Internal Comment"),
                inputType: "richeditor",
            },
        },
        removePost: {
            type: "boolean",
            default: "false",
            "x-control": {
                label: t("Set Post Visibility to Hidden"),
                inputType: "checkBox",
            },
        },
        removeMethod: {
            type: "string",
            default: "wipe",
            "x-control": {
                conditions: [{ field: "removePost", type: "boolean", const: true }],
                label: t("Remove Method"),
                inputType: "radio",
                choices: {
                    staticOptions: {
                        delete: t("Delete"),
                        wipe: t("Wipe"),
                    },
                },
            },
        },
    },
    required: ["name", "status"],
};

const initialFormValues: EscalateForm = {
    name: "",
    status: EscalationStatus.OPEN,
    removePost: false,
    removeMethod: "wipe",
    initialCommentBody: JSON.stringify([{ children: [{ text: "" }], type: "p" }]),
    initialCommentFormat: "rich2",
};

export function EscalateModal(props: IProps) {
    const { escalationType, report, record, recordType, isVisible, onClose } = props;

    const [values, setValues] = useState<EscalateForm>(initialFormValues);
    const toast = useToast();

    useEffect(() => {
        if (escalationType === "report" && report) {
            setValues((prev) => ({
                ...prev,
                name: report.recordName,
                status: "open",
            }));
        }
        if (escalationType === "record" && record) {
            if (recordType === "discussion") {
                setValues((prev) => ({
                    ...prev,
                    name: (record as IDiscussion).name,
                    status: "open",
                }));
            }
            if (recordType === "comment") {
                setValues((prev) => ({
                    ...prev,
                    name: (record as IComment).name,
                    status: "open",
                }));
            }
        }
    }, [escalationType, report, recordType, record, isVisible]);

    const schema = useMemo(() => {
        if (escalationType === "record") {
            const reasonProperty: PartialSchemaDefinition<EscalateForm> = {
                type: "array",
                items: {
                    type: "string",
                },
                "x-control": {
                    label: t("Report Reason"),
                    inputType: "dropDown",
                    multiple: true,
                    choices: {
                        api: {
                            searchUrl: "/api/v2/report-reasons?limit=100",
                            singleUrl: "/api/v2/report-reasons/%s",
                            labelKey: "name",
                            valueKey: "reportReasonID",
                        },
                    },
                },
            };

            const keyValues = Object.entries(ESCALATE_SCHEMA.properties);
            keyValues.splice(2, 0, ["reportReasonIDs", reasonProperty]);
            const newProperties = Object.fromEntries(keyValues);

            return {
                ...ESCALATE_SCHEMA,
                properties: newProperties,
                required: [...ESCALATE_SCHEMA.required, "reportReasonIDs"],
            };
        }
        return ESCALATE_SCHEMA;
    }, [escalationType]);

    const createEscalation = useMutation<IEscalation, IApiError, EscalateForm>({
        mutationFn: async (escalation) => {
            const makePayload = {
                name: escalation.name,
                status: escalation.status,
                assignedUserID: escalation.assignee,
                ...(escalation.initialCommentBody !== initialFormValues.initialCommentBody && {
                    initialCommentBody: escalation.initialCommentBody,
                    initialCommentFormat: "rich2",
                }),
                ...(escalation.removePost && {
                    removePost: escalation.removePost,
                    removeMethod: escalation.removeMethod,
                }),
                ...(escalationType === "report" &&
                    report && {
                        recordID: report?.recordID,
                        recordType: report?.recordType,
                        reportID: report?.reportID,
                    }),
                ...(escalationType === "record" &&
                    record && {
                        recordID: record?.["discussionID"] ?? record?.["commentID"],
                        recordType: recordType,
                        reportReasonIDs: escalation.reportReasonIDs,
                    }),
            };
            const response = await apiv2.post(`/escalations`, makePayload);
            return response.data;
        },
        onSuccess: (data) => {
            toast.addToast({
                autoDismiss: false,
                body: (
                    <Translate
                        source={"Escalation created. Go to: <0/>"}
                        c0={() => (
                            <SmartLink to={`/dashboard/content/escalations/${data.escalationID}`}>
                                {data.name}
                            </SmartLink>
                        )}
                    />
                ),
            });
            onClose();
        },
    });

    const handleClose = () => {
        setValues(initialFormValues);
        onClose();
    };

    return (
        <Modal isVisible={isVisible} exitHandler={handleClose} size={ModalSizes.MEDIUM}>
            <Frame
                header={<FrameHeader title={t("Create Escalation")} closeFrame={handleClose} />}
                body={
                    <FrameBody hasVerticalPadding>
                        <JsonSchemaForm
                            disabled={createEscalation.isLoading}
                            fieldErrors={createEscalation?.error?.response.data?.errors}
                            schema={schema}
                            instance={values}
                            FormControlGroup={FormControlGroup}
                            FormControl={FormControlWithNewDropdown}
                            onChange={setValues}
                        />
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button onClick={handleClose} buttonType={ButtonTypes.TEXT}>
                            {t("Cancel")}
                        </Button>
                        <Button
                            onClick={() => {
                                createEscalation.mutateAsync(values);
                            }}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                        >
                            {createEscalation.isLoading ? <ButtonLoader /> : t("Create")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}
