/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { useDiscussionPatch } from "@library/features/discussions/discussionHooks";
import DropDownSwitchButton from "@library/flyouts/DropDownSwitchButton";
import { t } from "@vanilla/i18n";

export function DiscussionOptionsResolve(props: {
    discussion: Pick<IDiscussion, "discussionID" | "resolved">;
    onSuccess?: () => Promise<void>;
}) {
    const { discussionID, resolved = false } = props.discussion;
    const { onSuccess } = props;
    const { patchDiscussion, isLoading } = useDiscussionPatch(discussionID, "resolved");

    return (
        <DropDownSwitchButton
            label={resolved ? t("Unresolve") : t("Resolve")}
            isLoading={isLoading}
            onClick={async () => {
                await patchDiscussion({ resolved: !resolved });
                !!onSuccess && (await onSuccess());
            }}
            status={resolved}
        />
    );
}
