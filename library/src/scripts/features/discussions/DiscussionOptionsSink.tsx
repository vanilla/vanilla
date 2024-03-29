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
    onSuccess?: () => Promise<void>;
}

export function DiscussionOptionsSink(props: IProps) {
    const { discussionID, sink = false } = props.discussion;
    const { onSuccess } = props;
    const { patchDiscussion, isLoading } = useDiscussionPatch(discussionID, "sink");

    return (
        <DropDownSwitchButton
            label={sink ? t("Sunk") : t("Sink")}
            isLoading={isLoading}
            onClick={async () => {
                await patchDiscussion({ sink: !sink });
                !!onSuccess && (await onSuccess());
            }}
            status={sink}
        />
    );
}
