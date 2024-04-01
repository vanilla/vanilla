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
                details={[
                    { label: "Name", value: "Willy Wonka" },
                    { label: "Title", value: "President" },
                    { label: "Company", value: "Wonka Candy Company" },
                    { label: "Favourite Color", value: "Metallic Green" },
                    { label: "More Information", value: "Lorem Ipsum Dolor Sit Amet" },
                    { label: "Subcontractor", value: "Meta" },
                    { label: "Subcontractor", value: "Meta" },
                    { label: "Date of first contact", value: "June 4" },
                    { label: "Subcontractor", value: "Meta" },
                    {
                        label: "Some Longer Titled Information goes here and wraps around and that's OK if it needs to do that",
                        value: "This one is also a lot longer than we'd like it to be but that's OK",
                    },
                ]}
            />
        </div>
    );
}
