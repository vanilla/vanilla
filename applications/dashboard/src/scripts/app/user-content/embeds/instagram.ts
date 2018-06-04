/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@dashboard/embeds";

// Setup image embeds.
registerEmbed("instagram", renderInstagram);

export async function renderInstagram(element: HTMLElement, data: IEmbedData) {
    element.classList.add("embed-image");
    element.classList.add("embedImage");
    element.innerHTML = `<iframe src="https://instagram.com/p/${data.attributes.postID}/embed/" width=${
        data.width
    } height=${data.height} frameborder="0" scrolling="no" allowtransparency="true"></iframe>
        </div>`;
}
