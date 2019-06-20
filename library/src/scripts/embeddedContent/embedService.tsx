/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { QuoteEmbed } from "@library/embeddedContent/QuoteEmbed";
import { mountReact } from "@library/dom/domUtils";
import { logWarning, logDebug, debug } from "@vanilla/utils";
import { onReady } from "@library/utility/appUtils";
import { element } from "prop-types";
import Attachment from "@library/content/attachments/Attachment";
import { FileEmbed } from "@library/embeddedContent/FileEmbed";
import { LinkEmbed } from "@library/embeddedContent/LinkEmbed";

// Methods
export interface IBaseEmbedProps {
    // Stored data.
    embedType: string;
    url: string;
    name?: string;
    // Frontend only
    inEditor?: boolean;
    // onRenderComplete: () => void;
}

type EmbedComponentType = React.ComponentType<IBaseEmbedProps>;

const registeredEmbeds = new Map<string, EmbedComponentType>();

export function registerEmbed(embedType: string, EmbedComponent: EmbedComponentType) {
    registeredEmbeds.set(embedType, EmbedComponent);
}

export function getEmbedForType(embedType: string): EmbedComponentType | null {
    return registeredEmbeds.get(embedType) || null;
}

export function mountEmbed(mountPoint: HTMLElement, data: IBaseEmbedProps, inEditor: boolean, callback?: () => void) {
    const type = data.embedType || null;
    if (type === null) {
        logWarning(`Found embed with data`, data, `and no type on element`, mountPoint);
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

    mountReact(<EmbedClass {...data} inEditor={inEditor} />, mountPoint, callback);
}

export function mountAllEmbeds(root: HTMLElement = document.body) {
    const mountPoints = root.querySelectorAll("[data-embedjson]");
    for (const mountPoint of mountPoints) {
        const parsedData = JSON.parse(mountPoint.getAttribute("data-embedjson") || "{}");
        mountEmbed(mountPoint as HTMLElement, parsedData, false);
    }
}

// Default embed registration
registerEmbed("quote", QuoteEmbed);
registerEmbed("file", FileEmbed);
registerEmbed("link", LinkEmbed);
