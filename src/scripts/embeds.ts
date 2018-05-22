import { logError } from "@core/utility";

/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

export interface IEmbedData {
    type: string;
    url: string;
    name: string | null;
    body: string | null;
    photoUrl: string | null;
    height: number | null;
    width: number | null;
    attributes: {
        [key: string]: any;
    };
}

export type embedRenderer = (element: HTMLElement, data: IEmbedData) => Promise<void>;

const embeds: {
    [type: string]: embedRenderer;
} = {};

/**
 * Get all of the registered embed types.
 */
export function getEmbedTypes() {
    return Object.keys(embeds);
}

/**
 * Register an embed rendering function.
 */
export function registerEmbed(type: string, renderer: embedRenderer) {
    embeds[type] = renderer;
}

/**
 * Render an embed into a DOM node based on it's type.
 */
export function renderEmbed(element: HTMLElement, data: IEmbedData): undefined | Promise<void> {
    element.classList.add("embed-" + data.type);

    if (!data.type) {
        logError("The embed type was not provided.");
        return;
    }

    const render = data.type && embeds[data.type];

    if (render) {
        return render(element, data);
    } else {
        logError("Could not find a renderer for the embed type - " + data.type);
        return;
    }
}
