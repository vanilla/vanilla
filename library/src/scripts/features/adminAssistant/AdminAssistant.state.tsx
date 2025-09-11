/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { ProductMessagesApi } from "@library/features/adminAssistant/ProductMessages.api";
import { useSessionStorage } from "@vanilla/react-utils";
import { useEffect, useState } from "react";

export type AdminAssistantState =
    | {
          type: "closed" | "root" | "messageInbox";
      }
    | {
          type: "messageDetails";
          productMessageID: string;
      };

/**
 * Handle state management for the admin assistant including initial state.
 */
export function useAdminAssistantState(params: {
    initialState?: AdminAssistantState;
    messagesQuery: Partial<ReturnType<typeof ProductMessagesApi.useListMessagesQuery>>;
}) {
    const { messagesQuery } = params;
    const [displayState, _setDisplayState] = useSessionStorage<AdminAssistantState>(
        "adminAssistantState",
        params.initialState ?? {
            type: "closed",
        },
    );
    const [didSetInitialState, setDidSetInitialState] = useState(!!params.initialState);
    const [wasAssistantClosedThisSession, setWasAssistantClosedThisSession] = useSessionStorage(
        "wasAssistantClosedThisSession",
        false,
    );

    function setDisplayState(newState: AdminAssistantState) {
        _setDisplayState(newState);
        if (newState.type === "closed") {
            setWasAssistantClosedThisSession(true);
        }
    }

    useEffect(() => {
        if (!messagesQuery.data) {
            // Data isn't loaded yet.
            return;
        }

        if (didSetInitialState) {
            // We've already set our initial state.
            return;
        }

        if (wasAssistantClosedThisSession) {
            // Use already dismissed the assistant this session.
            return;
        }

        const messages = messagesQuery.data;
        const firstDirectMessage = messages.find(
            (message) => message.announcementType === "Direct" && !message.isDismissed,
        );
        if (firstDirectMessage) {
            setDisplayState({
                type: "messageDetails",
                productMessageID: firstDirectMessage.productMessageID,
            });
            setDidSetInitialState(true);
            return;
        }

        // Check for any inbox messages that are not dismissed
        const firstInboxMessage = messages.find(
            (message) => message.announcementType === "Inbox" && !message.isDismissed,
        );
        if (firstInboxMessage) {
            setDisplayState({
                type: "messageInbox",
            });
            setDidSetInitialState(true);
            return;
        }

        // Check for any messages that are not dismissed
        const firstMessage = messages.find((message) => !message.isDismissed);
        if (firstMessage) {
            setDisplayState({
                type: "messageInbox",
            });
            setDidSetInitialState(true);
            return;
        }
    }, [didSetInitialState, wasAssistantClosedThisSession, messagesQuery.isLoading]);
    return [displayState, setDisplayState] as const;
}
