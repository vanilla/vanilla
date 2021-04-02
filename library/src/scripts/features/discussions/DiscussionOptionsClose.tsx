/**
 * @author Adam Charron <adam.c@vanillaforums.com>
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

export function DiscussionOptionsClose(props: IProps) {
    const { discussionID, closed } = props.discussion;
    const { patchDiscussion, isLoading } = useDiscussionPatch(discussionID, "close");

    return (
        <DropDownSwitchButton
            label={closed ? t("Closed") : t("Close")}
            isLoading={isLoading}
            onClick={() => {
                patchDiscussion({ closed: !closed });
            }}
            status={closed}
        />
    );
}
