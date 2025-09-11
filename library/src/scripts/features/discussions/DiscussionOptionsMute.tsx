/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import DropDownSwitchButton from "@library/flyouts/DropDownSwitchButton";
import { t } from "@library/utility/appUtils";
import { useMutation } from "@tanstack/react-query";
import { useDiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { useEffect } from "react";

interface IMuteDiscussionModalProps {
    discussion: Pick<IDiscussion, "discussionID" | "muted">;
    onSuccess?: (muted?: boolean) => Promise<void>;
}

export function DiscussionOptionsMute(props: IMuteDiscussionModalProps) {
    const toast = useToast();
    const {
        discussion: { discussionID, muted },
        onSuccess,
    } = props;

    const { mute } = useDiscussionsApi();

    const { mutateAsync: handleDiscussionMute, isLoading } = useMutation({
        mutationFn: async (muted: boolean) =>
            await mute(discussionID, {
                muted,
            }),
    });

    async function handleSuccess(muted: boolean) {
        toast.addToast({
            autoDismiss: true,
            body: <>{muted ? t("Discussion has been muted.") : t("Discussion has been unmuted.")}</>,
        });
        await onSuccess?.(muted);
    }

    function handleError(e: IError) {
        toast.addToast({
            autoDismiss: false,
            dismissible: true,
            body: <>{e.description ?? e.message}</>,
        });
    }

    async function handleClick() {
        try {
            const response = await handleDiscussionMute(!muted);
            await handleSuccess(response.muted);
        } catch (e) {
            handleError(e);
        }
    }

    return (
        <DropDownSwitchButton
            renderCheckIcon={false}
            isLoading={isLoading}
            status={!!muted}
            label={muted ? t("Unmute") : t("Mute")}
            onClick={handleClick}
        />
    );
}

// this is for legacy views
export function MuteDiscussionModal(props: IMuteDiscussionModalProps) {
    const {
        discussion: { discussionID, muted = false },
        onSuccess,
    } = props;

    const toast = useToast();
    const { mute } = useDiscussionsApi();

    const { mutateAsync: handleDiscussionMute } = useMutation({
        mutationFn: async (muted: boolean) =>
            await mute(discussionID, {
                muted,
            }),
    });

    async function handleSuccess(muted: boolean) {
        toast.addToast({
            autoDismiss: true,
            body: <>{muted ? t("Discussion has been muted.") : t("Discussion has been unmuted.")}</>,
        });
        await onSuccess?.(muted);
    }

    function handleError(e: IError) {
        toast.addToast({
            autoDismiss: false,
            dismissible: true,
            body: <>{e.description ?? e.message}</>,
        });
    }

    async function handleMute(muted: boolean) {
        try {
            const response = await handleDiscussionMute(!muted);
            await handleSuccess(response.muted);
        } catch (e) {
            handleError(e);
        }
    }

    useEffect(() => {
        void handleMute(muted);
    }, []);

    return <></>;
}
