/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { useDiscussionPatch } from "@library/features/discussions/discussionHooks";
import DropDownSwitchButton from "@library/flyouts/DropDownSwitchButton";
import { t } from "@vanilla/i18n";
import React from "react";

interface IProps {
    discussion: IDiscussion;
}

export function DiscussionOptionsResolve(props: IProps) {
    const { discussionID, resolved = false } = props.discussion;
    const { patchDiscussion, isLoading } = useDiscussionPatch(discussionID, "resolved");

    return (
        <DropDownSwitchButton
            label={resolved ? t("Unresolve") : t("Resolve")}
            isLoading={isLoading}
            onClick={() => {
                patchDiscussion({ resolved: !resolved });
            }}
            status={resolved}
        />
    );
}
