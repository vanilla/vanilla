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

window.embeds = embeds;

export function getEmbedTypes() {
    return Object.keys(embeds);
}

export function registerEmbed(type: string, renderer: embedRenderer) {
    embeds[type] = renderer;
}

export function renderEmbed(element: HTMLElement, data: IEmbedData): Promise<void> {
    return embeds[data.type](element, data);
}
