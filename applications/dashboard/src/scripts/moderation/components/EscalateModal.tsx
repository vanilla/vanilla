/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IPostEscalation, IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { IUser } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
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
import { useMutation, useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { JSONSchemaType, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { useEffect, useState } from "react";

interface IProps {
    recordID: IReport["recordID"] | null;
    recordType: IReport["recordType"] | null;
    report: IReport | null;
    isVisible: boolean;
    onClose: () => void;
}

interface EscalateForm {
    name: IPostEscalation["name"];
    status: IPostEscalation["status"]; // some enum here
    assignee?: IUser["userID"];
    noteBody: IPostEscalation["noteBody"];
    noteFormat: IPostEscalation["noteFormat"];
    removePost: boolean;
    removeMethod: IPostEscalation["removeMethod"];
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
                    staticOptions: {
                        open: t("Open"),
                        "in-progress": t("In Progress"),
                        "on-hold": t("On Hold"),
                        done: t("Done"),
                        "external-zendesk": t("Moved to Zendesk"),
                    },
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
        noteBody: {
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
    status: "",
    removePost: false,
    removeMethod: "wipe",
    noteBody: JSON.stringify([{ children: [{ text: "" }], type: "p" }]),
    noteFormat: "rich2",
};

export function EscalateModal(props: IProps) {
    const { report, isVisible, onClose } = props;
    const { recordID, recordType } = report ?? {};

    const [values, setValues] = useState<EscalateForm>(initialFormValues);
    const toast = useToast();

    const record = useQuery<any, IError, IDiscussion | IComment>({
        queryFn: async () => {
            const response = await apiv2.get(`/${report?.recordType}s/${report?.recordID}`);
            return response.data;
        },
        queryKey: ["report", recordID, recordType, report?.recordType, report?.recordID],
        enabled: !!report,
    });

    const createEscalation = useMutation<any, IError, EscalateForm>({
        mutationFn: async (escalation) => {
            if (report) {
                const makePayload: IPostEscalation = {
                    name: escalation.name,
                    status: escalation.status,
                    assignedUserID: escalation.assignee,
                    noteBody: escalation.noteBody,
                    noteFormat: "rich2",
                    ...(escalation.removePost && {
                        removePost: escalation.removePost,
                        removeMethod: escalation.removeMethod,
                    }),
                    recordID: report?.recordID,
                    recordType: report?.recordType,
                    reportID: report?.reportID,
                };
                const response = await apiv2.post(`/escalations`, makePayload);
                return response.data;
            }
            return null;
        },
        onSuccess: () => {
            toast.addToast({
                autoDismiss: true,
                body: t("Escalation created"),
            });
            onClose();
        },
    });

    useEffect(() => {
        if (record?.data) {
            setValues((prev) => ({
                ...prev,
                name: record.data.name,
            }));
        }
    }, [record.data]);

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
                        {record.isLoading && <div>Loading...</div>}
                        {record.isError && <div>Error loading reports</div>}
                        {record.isSuccess && (
                            <JsonSchemaForm
                                disabled={createEscalation.isLoading}
                                // fieldErrors={error?.errors ?? {}}
                                schema={ESCALATE_SCHEMA}
                                instance={values}
                                FormControlGroup={FormControlGroup}
                                FormControl={FormControlWithNewDropdown}
                                onChange={setValues}
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
