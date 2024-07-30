/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import AttachmentLayoutComponent from "@library/features/discussions/integrations/components/AttachmentLayout";
import { STORY_USER } from "@library/storybook/storyData";
import { Icon } from "@vanilla/icons";

export default {
    title: "Attachments",
    parameters: {},
};

export function AttachmentLayout(props: {}) {
    return (
        <div
            style={{
                maxWidth: "850px",
            }}
        >
            <AttachmentLayoutComponent
                title={"Salesforce - Lead"}
                notice={"Working - Contacted"}
                url={"https://www.salesforce.com"}
                id={"23456765342"}
                idLabel="Lead #"
                dateUpdated={"2021-02-03 17:51:15"}
                user={STORY_USER}
                icon={<Icon icon={"logo-salesforce"} height={60} width={60} />}
                metadata={[
                    { labelCode: "Name", value: "Willy Wonka" },
                    { labelCode: "Title", value: "President" },
                    { labelCode: "Company", value: "Wonka Candy Company", url: "https://google.com" },
                    { labelCode: "Favourite Color", value: "Metallic Green" },
                    { labelCode: "More Information", value: "Lorem Ipsum Dolor Sit Amet" },
                    { labelCode: "Subcontractor", value: "Meta" },
                    { labelCode: "Subcontractor", value: "Meta" },
                    { labelCode: "Date of first contact", value: "2023-01-04T12:42:04Z", format: "date-time" },
                    { labelCode: "Subcontractor", value: "Meta" },
                    {
                        labelCode:
                            "Some Longer Titled Information goes here and wraps around and that's OK if it needs to do that",
                        value: "This one is also a lot longer than we'd like it to be but that's OK",
                    },
                ]}
            />
        </div>
    );
}

export function EscalationAttachment(props: {}) {
    return (
        <div
            style={{
                maxWidth: "850px",
            }}
        >
            <AttachmentLayoutComponent
                title={"Vanilla Escalation"}
                notice={"Open"}
                url={"https://www.vanillaforums.com"}
                dateUpdated={"2021-02-03 17:51:15"}
                user={STORY_USER}
                icon={<Icon icon={"vanilla-logo"} height={60} width={60} />}
                metadata={[
                    {
                        labelCode: "Name",
                        value: "Name of the escalation here",
                    },
                    {
                        labelCode: "Assignee",
                        value: STORY_USER["name"],
                        format: "user",
                        userFragment: STORY_USER,
                    },
                    {
                        labelCode: "Number of Reports",
                        value: 12,
                    },
                    {
                        labelCode: "Number of Comments",
                        value: 3,
                    },
                    {
                        labelCode: "Report Reasons",
                        value: ["Reason 1", "Reason 2", "Reason 3"],
                    },
                    {
                        labelCode: "Last Reported",
                        value: "1990-08-20T04:00:00Z",
                        format: "date-time",
                    },
                ]}
            />
        </div>
    );
}
