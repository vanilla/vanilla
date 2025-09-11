/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ReactNode, createContext, useContext, useEffect, useState } from "react";
import { RecordID, logDebug } from "@vanilla/utils";
import { getMeta, getSiteSection, setMeta } from "@library/utility/appUtils";

import { IUserSuggestion } from "@library/features/users/suggestion/IUserSuggestion";
import apiv2 from "@library/apiv2";
import { useQuery } from "@tanstack/react-query";

interface IMentionsContextValue {
    recordID?: RecordID;
    recordType?: string;
    username: string | null;
    setUsername: (username: string | null) => void;
    resetUsername: () => void;
    suggestedUsers: IUserSuggestion[];
    isLoading: boolean;
    error: any;
    lastSuccessfulUsername: string | null;
    activeSuggestionID: string;
    activeSuggestionIndex: number;
    setActive: (suggestionID: string, suggestionIndex: number) => void;
}

interface IMentionsProviderProps {
    children: ReactNode;
    recordID?: RecordID;
    recordType?: "category" | "group" | "discussion" | "escalation" | string;
}

const DEFAULT_MENTIONS_VALUE: IMentionsContextValue = {
    recordID: undefined,
    recordType: undefined,
    username: null,
    setUsername: () => null,
    resetUsername: () => null,
    suggestedUsers: [],
    isLoading: false,
    error: null,
    lastSuccessfulUsername: null,
    activeSuggestionID: "",
    activeSuggestionIndex: 0,
    setActive: () => null,
};

const MentionsContext = createContext<IMentionsContextValue>(DEFAULT_MENTIONS_VALUE);

const USER_LIMIT = 50;

export function MentionsProvider(props: IMentionsProviderProps) {
    const [recordID, setRecordID] = useState<RecordID | undefined>(props.recordID?.toString());
    const [recordType, setRecordType] = useState<string | undefined>(props.recordType);
    const [username, setUsername] = useState<string | null>(null);
    const [lastSuccessfulUsername, setLastSuccessfulUsername] = useState<string | null>(null);
    const [activeSuggestionID, setActiveSuggestionID] = useState<string>("");
    const [activeSuggestionIndex, setActiveSuggestionIndex] = useState<number>(0);

    useEffect(() => {
        setRecordID(props.recordID);
    }, [props.recordID]);

    // AIDEV-NOTE: Handles legacy mentions.data event from CategoryPicker
    useEffect(() => {
        function handleLegacyMentionData(event) {
            const { recordType, recordID } = event.detail;
            setRecordType(recordType);
            setRecordID(recordID?.toString());
        }

        window.addEventListener("mentions.data", handleLegacyMentionData);
        setMeta("mentions.handlerAdded", true);

        return () => {
            window.removeEventListener("mentions.data", handleLegacyMentionData);
        };
    }, []);

    const resetUsername = () => setUsername(null);

    const setActive = (suggestionID: string, suggestionIndex: number) => {
        setActiveSuggestionID(suggestionID);
        setActiveSuggestionIndex(suggestionIndex);
    };

    const {
        data: suggestedUsers = [],
        isLoading,
        error,
    } = useQuery({
        queryKey: ["userSuggestions", username, recordType, recordID],
        queryFn: async () => {
            if (!username || username.trim() === "") {
                return [];
            }

            const params = {
                name: username + "*",
                siteSectionID: getSiteSection().sectionID,
                order: "mention",
                limit: USER_LIMIT,
                ...(recordType && recordID && { recordType, recordID }),
            };

            const response = await apiv2.get("/users/by-names/", { params });

            // Add unique domIDs to each user.
            response.data = response.data.map((data) => {
                data.domID = "mentionSuggestion" + data.userID;
                return data;
            });

            return response.data;
        },
        enabled: Boolean(username && username.trim() !== ""),
    });

    // Update lastSuccessfulUsername and activeSuggestionID when we get successful results
    useEffect(() => {
        if (suggestedUsers.length > 0 && username) {
            setLastSuccessfulUsername(username);
            // Set the first user as active by default
            const firstUserID = suggestedUsers[0].domID;
            setActiveSuggestionID(firstUserID);
            setActiveSuggestionIndex(0);
        }
    }, [suggestedUsers, username]);

    const value: IMentionsContextValue = {
        recordID,
        recordType,
        username,
        setUsername,
        resetUsername,
        suggestedUsers,
        isLoading,
        error,
        lastSuccessfulUsername,
        activeSuggestionID,
        activeSuggestionIndex,
        setActive,
    };

    return <MentionsContext.Provider value={value}>{props.children}</MentionsContext.Provider>;
}

export function useMentions() {
    const context = useContext(MentionsContext);
    if (context === DEFAULT_MENTIONS_VALUE) {
        logDebug("useMentions must be used within a MentionsProvider. Returning empty state.");
        return DEFAULT_MENTIONS_VALUE;
    }
    return context;
}
