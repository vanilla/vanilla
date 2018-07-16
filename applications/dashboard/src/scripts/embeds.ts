/**
 * Embed utility functions and types.
 * This file should have NO external dependencies other than javascript.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { capitalizeFirstLetter, logError } from "@dashboard/utility";

export const FOCUS_CLASS = "embed-focusableElement";

export interface IEmbedData {
    type: string;
    url: string;
    name?: string | null;
    body?: string | null;
    photoUrl?: string | null;
    height?: number | null;
    width?: number | null;
    attributes: {
        [key: string]: any;
    };
}

export interface IEmbedElements {
    root: HTMLElement;
    content: HTMLElement;
}

export type EmbedRenderer = (elements: IEmbedElements, data: IEmbedData, inEditor: boolean) => Promise<void>;

const embedRenderers: {
    [type: string]: EmbedRenderer;
} = {};

/**
 * Get all of the registered embed types.
 */
export function getEditorEmbedTypes() {
    return Object.keys(embedRenderers);
}

/**
 * Register an embed rendering function.
 */
export function registerEmbed(type: string, renderer: EmbedRenderer) {
    embedRenderers[type] = renderer;
}

/**
 * Render an embed into a DOM node based on it's type.
 */
export function renderEmbed(elements: IEmbedElements, data: IEmbedData, inEditor = true): Promise<void> {
    if (!data.type) {
        throw new Error("The embed type was not provided.");
    }

    const render = data.type && embedRenderers[data.type];

    if (render) {
        return render(elements, data, inEditor);
    } else {
        throw new Error("Could not find a renderer for the embed type - " + data.type);
    }
}
