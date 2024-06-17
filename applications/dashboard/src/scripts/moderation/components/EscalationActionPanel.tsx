/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import { EscalationAssignee } from "@dashboard/moderation/components/EscalationAssignee";
import { css, cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import { inputClasses } from "@library/forms/inputStyles";
import { PageBox } from "@library/layout/PageBox";
import PageHeading from "@library/layout/PageHeading";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { FormGroupLabel } from "@vanilla/ui";
import { DropDownArrow } from "@vanilla/ui/src/forms/shared/DropDownArrow";

interface IProps {
    escalation: IEscalation;
}

const classes = {
    root: css({
        maxWidth: 240,
    }),
    input: css({
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        gap: 6,

        "& input": {
            margin: 0,
        },
        "&&": {
            marginTop: -4,
            marginBottom: 12,
        },
    }),
};

export function EscalationActionPanel(props: IProps) {
    const { escalation } = props;
    const formSchema: JsonSchema = {
        type: "object",
        properties: {
            status: {
                type: "string",
                enum: ["Approve", "Reject", "Dismiss"],
                "x-control": {
                    type: "text",
                    inputType: "dropDown",
                    label: "Status",
                    choices: {
                        staticOptions: {
                            open: "Open",
                            "in-progress": "In Progress",
                            "on-hold": "On Hold",
                            "under-review": "Under Review",
                            "escalated-external": "Escalated Externally",
                            done: "Done",
                        },
                    },
                },
            },
            // assignedUserID: {
            //     type: "number",
            //     "x-control": {
            //         type: "number",
            //         inputType: "dropDown",
            //         label: "Assigned User",
            //         placeholder: "Select a user",
            //         choices: {
            //             api: {
            //                 searchUrl: "/api/v2/users/by-name?query=%s",
            //                 labelKey: "name",
            //                 valueKey: "userID",
            //                 singleUrl: "/api/v2/users/%s",
            //             },
            //         },
            //     },
            // },
        },
        required: ["status"],
    };

    return (
        <PageBox options={{ borderType: BorderType.SHADOW }} className={classes.root}>
            <PageHeading depth={5} includeBackLink={false} title={"Actions"} />
            <FormGroupLabel>Status</FormGroupLabel>
            <div className={cx(inputClasses().inputText, classes.input)}>
                <input
                    type="text"
                    value={escalation.status}
                    onChange={() => {}}
                    style={{ appearance: "none", border: "none", outline: "none", padding: 0 }}
                />
                <DropDownArrow />
            </div>
            <FormGroupLabel>Assignee</FormGroupLabel>
            <div className={cx(classes.input)}>
                <EscalationAssignee escalation={escalation} />
            </div>

            <Button>{escalation.recordIsLive ? "Remove Post" : "Restore Post"}</Button>
        </PageBox>
    );
}
