import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import { logDebug } from "@vanilla/utils";

export namespace FragmentEditorCommunication {
    export type ContentUpdateMessage = {
        type: "contentUpdate";
        javascript?: string;
        css?: string;
        previewData?: IFragmentPreviewData;
    };

    export type PreviewAlignment = "none" | "center";

    export type PreviewSettingsMessage = {
        type: "previewSettings";
        alignment?: PreviewAlignment;
        previewThemeID?: string;
        previewRoleIDs?: number[];
    };

    export type PreviewLoadedMessage = {
        type: "previewLoadedAck";
    };

    export type RerenderMessage = {
        type: "rerender";
    };

    export type Message = ContentUpdateMessage | PreviewLoadedMessage | PreviewSettingsMessage | RerenderMessage;
}

export class FragmentEditorCommunication {
    public constructor(private ownWindow: Window | null, private targetWindow: Window | null) {}

    public onMessage = (handler: (message: FragmentEditorCommunication.Message) => void) => {
        const actualHandler = (message: MessageEvent) => {
            if (message.origin !== window.origin) {
                return;
            }

            if (message.data?.source !== "vanilla") {
                return;
            }

            handler(message.data);
        };

        this.ownWindow?.addEventListener("message", actualHandler, false);

        return () => {
            this.ownWindow?.removeEventListener("message", actualHandler);
        };
    };

    public sendMessage = (message: FragmentEditorCommunication.Message) => {
        if (this.targetWindow) {
            this.targetWindow?.postMessage({ source: "vanilla", ...message }, window.origin);
        }
    };
}
