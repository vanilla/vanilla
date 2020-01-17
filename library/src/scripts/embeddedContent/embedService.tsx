/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { mountReact } from "@vanilla/react-utils";
import { CodePenEmbed } from "@library/embeddedContent/CodePenEmbed";
import { FileEmbed } from "@library/embeddedContent/FileEmbed";
import { GettyImagesEmbed } from "@library/embeddedContent/GettyImagesEmbed";
import { GiphyEmbed } from "@library/embeddedContent/GiphyEmbed";
import { ImageEmbed } from "@library/embeddedContent/ImageEmbed";
import { ImgurEmbed } from "@library/embeddedContent/ImgurEmbed";
import { InstagramEmbed } from "@library/embeddedContent/InstagramEmbed";
import { LinkEmbed } from "@library/embeddedContent/LinkEmbed";
import { QuoteEmbed } from "@library/embeddedContent/QuoteEmbed";
import { SoundCloudEmbed } from "@library/embeddedContent/SoundCloudEmbed";
import { convertTwitterEmbeds, TwitterEmbed } from "@library/embeddedContent/TwitterEmbed";
import { VideoEmbed } from "@library/embeddedContent/VideoEmbed";
import { onContent } from "@library/utility/appUtils";
import { logWarning } from "@vanilla/utils";
import React, { useContext } from "react";
import Quill from "quill/core";
import { EmbedErrorBoundary } from "@library/embeddedContent/EmbedErrorBoundary";

export const FOCUS_CLASS = "embed-focusableElement";

interface IEmbedContext {
    inEditor?: boolean;
    onRenderComplete?: () => void;
    syncBackEmbedValue?: (values: object) => void;
    quill?: Quill | null;
    isSelected?: boolean;
    selectSelf?: () => void;
    deleteSelf?: () => void;
}

// Methods
export interface IBaseEmbedProps extends IEmbedContext {
    // Stored data.
    embedType: string;
    url: string;
    name?: string;
}

type EmbedComponentType = React.ComponentType<IBaseEmbedProps> & {
    async?: boolean;
};

const registeredEmbeds = new Map<string, EmbedComponentType>();

export function registerEmbed(embedType: string, EmbedComponent: EmbedComponentType) {
    registeredEmbeds.set(embedType, EmbedComponent);
}

export function getEmbedForType(embedType: string): EmbedComponentType | null {
    return registeredEmbeds.get(embedType) || null;
}

export const EmbedContext = React.createContext<IEmbedContext>({});
export function useEmbedContext() {
    return useContext(EmbedContext);
}

export async function mountEmbed(mountPoint: HTMLElement, data: IBaseEmbedProps, inEditor: boolean) {
    return new Promise((resolve, reject) => {
        const type = data.embedType || null;
        if (type === null) {
            logWarning(`Found embed with data`, data, `and no type on element`, mountPoint);
            return;
        }
        const exception: string | null = "exception" in data ? data["exception"] : null;
        if (exception !== null) {
            logWarning(`Found embed with data`, data, `and and exception`, exception, ` on element`, mountPoint);
            return;
        }
        const EmbedClass = getEmbedForType(type);
        if (EmbedClass === null) {
            logWarning(
                `Attempted to mount embed type ${type} on element`,
                mountPoint,
                `but could not find registered embed.`,
            );
            return;
        }
        mountPoint.removeAttribute("data-embedJson");

        const isAsync = EmbedClass.async;
        const onMountComplete = () => resolve();
        // If the component is flagged as async, then it will confirm when the render is complete.
        mountReact(
            <EmbedErrorBoundary url={data.url}>
                <EmbedContext.Provider value={data}>
                    <EmbedClass
                        {...data}
                        inEditor={inEditor}
                        onRenderComplete={isAsync ? onMountComplete : undefined}
                    />
                </EmbedContext.Provider>
            </EmbedErrorBoundary>,
            mountPoint,
            !isAsync ? onMountComplete : undefined,
        );
    });
}

export async function mountAllEmbeds(root: HTMLElement = document.body) {
    const mountPoints = root.querySelectorAll("[data-embedjson]");
    const promises = Array.from(mountPoints).map(mountPoint => {
        const parsedData = JSON.parse(mountPoint.getAttribute("data-embedjson") || "{}");
        return mountEmbed(mountPoint as HTMLElement, parsedData, false);
    });
    await Promise.all(promises);
}

// Default embed registration
registerEmbed("codepen", CodePenEmbed);
registerEmbed("file", FileEmbed);
registerEmbed("gettyimages", GettyImagesEmbed);
registerEmbed("getty", GettyImagesEmbed);
registerEmbed("giphy", GiphyEmbed);
registerEmbed("imgur", ImgurEmbed);
registerEmbed("instagram", InstagramEmbed);
registerEmbed("link", LinkEmbed);
registerEmbed("quote", QuoteEmbed);
registerEmbed("soundcloud", SoundCloudEmbed);
registerEmbed("twitch", VideoEmbed);
registerEmbed("twitter", TwitterEmbed);
registerEmbed("vimeo", VideoEmbed);
registerEmbed("wistia", VideoEmbed);
registerEmbed("youtube", VideoEmbed);
registerEmbed("image", ImageEmbed);

// This is specifically required because of some legacy formats that don't render
// The embed json format. Twitter was converted out of global JS and merged here.
onContent(convertTwitterEmbeds);
