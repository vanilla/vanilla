/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { t } from "@vanilla/i18n";
import { RecordID, guessOperatingSystem, OS } from "@vanilla/utils";
import React, { useContext } from "react";

export interface IThreadItemContext {
    recordType: "discussion" | "comment";
    recordID: RecordID;
    recordUrl: string;
    timestamp: string;
    name: string;
    handleCopyUrl: () => Promise<void>;
    handleNativeShare?: () => Promise<void>;
    emailUrl: string;
}

const ThreadItemContext = React.createContext<IThreadItemContext>({
    recordType: "discussion",
    recordID: 0,
    recordUrl: "",
    timestamp: "",
    name: "",
    handleCopyUrl: async () => {},
    emailUrl: "",
});

export function ThreadItemContextProvider(
    props: { children: React.ReactNode } & Omit<IThreadItemContext, "handleCopyUrl" | "handleNativeShare" | "emailUrl">,
) {
    const { children, ...contextProps } = props;
    const { recordUrl, name } = contextProps;

    const urlToShare = `${recordUrl}?utm_source=community-share`;

    const shareData = {
        url: urlToShare,
        text: t("Check out this post:"),
    };

    const emailSubject = `${t("Check it out:")} ${name}`;
    const emailUrl = `mailto:?subject=${emailSubject}&body=${shareData.text} ${encodeURIComponent(shareData.url)}`;

    const os = guessOperatingSystem();
    const nativeShareAvailable = navigator.share !== undefined && navigator.canShare?.(shareData);
    const useNativeShare = nativeShareAvailable && [OS.ANDROID, OS.IOS].includes(os);

    const handleNativeShare = useNativeShare
        ? async function () {
              await navigator.share(shareData);
          }
        : undefined;

    async function handleCopyUrl() {
        await navigator.clipboard.writeText(urlToShare);
    }

    const contextValue = {
        ...contextProps,
        handleCopyUrl,
        handleNativeShare,
        emailUrl,
    };

    return <ThreadItemContext.Provider value={contextValue}>{props.children}</ThreadItemContext.Provider>;
}

export function useThreadItemContext() {
    return useContext(ThreadItemContext);
}
