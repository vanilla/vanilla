/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { FunctionComponent } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { t } from "@library/utility/appUtils";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { useMutation } from "@tanstack/react-query";
import DiscussionsApi from "@vanilla/addon-vanilla/thread/DiscussionsApi";
import { useToast } from "@library/features/toaster/ToastContext";
import { IError } from "@library/errorPages/CoreErrorMessages";

const DiscussionOptionsBump: FunctionComponent<{ discussion: IDiscussion; onSuccess?: () => Promise<void> }> = ({
    discussion,
    onSuccess,
}) => {
    const { discussionID } = discussion;

    const toast = useToast();

    const mutation = useMutation({
        mutationFn: async () => await DiscussionsApi.bump(discussionID),
    });

    async function handleSuccess() {
        toast.addToast({
            autoDismiss: true,
            body: <>{t("Success! This post has been bumped.")}</>,
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
        <DropDownItemButton
            onClick={async () => {
                try {
                    await mutation.mutateAsync();
                    await handleSuccess();
                } catch (e) {
                    handleError(e);
                }
            }}
        >
            {t("Bump")}
        </DropDownItemButton>
    );
};

export default DiscussionOptionsBump;
