/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { FunctionComponent } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { t } from "@library/utility/appUtils";
import { useMutation } from "@tanstack/react-query";
import { DiscussionsApi } from "@vanilla/addon-vanilla/thread/DiscussionsApi";
import { useToast } from "@library/features/toaster/ToastContext";
import { IError } from "@library/errorPages/CoreErrorMessages";
import DropDownSwitchButton from "@library/flyouts/DropDownSwitchButton";

const DiscussionOptionsDismiss: FunctionComponent<{ discussion: IDiscussion; onSuccess?: () => Promise<void> }> = ({
    discussion,
    onSuccess,
}) => {
    const toast = useToast();

    const { discussionID, dismissed = false } = discussion;

    const mutation = useMutation({
        mutationFn: async (dismissed: boolean) =>
            await DiscussionsApi.dismiss(discussionID, {
                dismissed,
            }),
    });

    async function handleSuccess(dismissed: boolean) {
        toast.addToast({
            autoDismiss: true,
            body: (
                <>{dismissed ? t("Success. Announcement has been dismissed.") : t("Success. Dismissal cancelled.")}</>
            ),
        });
        !!onSuccess && (await onSuccess());
    }

    function handleError(e: IError) {
        toast.addToast({
            autoDismiss: false,
            dismissible: true,
            body: <>{e.description ?? e.message}</>,
        });
    }

    return (
        <DropDownSwitchButton
            label={dismissed ? t("Dismissed") : t("Dismiss")}
            isLoading={mutation.isLoading}
            onClick={async () => {
                try {
                    const response = await mutation.mutateAsync(!dismissed);
                    await handleSuccess(response.dismissed);
                } catch (e) {
                    handleError(e);
                }
            }}
            status={dismissed}
        />
    );
};

export default DiscussionOptionsDismiss;
