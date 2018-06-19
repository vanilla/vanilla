/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@dashboard/embeds";
import { ensureScript } from "@dashboard/dom";
import { onContent } from "@dashboard/application";

// Setup getty embeds.
onContent(convertgettyEmbeds);
registerEmbed("getty", rendergetty);

/**
 * Renders posted getty embeds.
 */
async function convertgettyEmbeds() {
    window.gie =
        window.gie ||
        function(c) {
            (window.gie.q = window.gie.q || []).push(c);
        };
    window.gie(function() {
        window.gie.widgets.load({
            id: "ZYNytzqrQIdsHgP0qC0CMg",
            sig: "soXRpEhFDK6-AacEMxzJSZQn1OdFI7k2tYbV4uHnWew=",
            w: "508px",
            h: "339px",
            items: "863162608",
            caption: false,
            tld: "ca",
            is360: false,
        });
    });
}

/**
 * Render a single getty embed.
 */
export async function rendergetty(element: HTMLElement, data: IEmbedData) {
    //  const gettyClass = data.attributes.gettyClass;
    await ensureScript("//embed-cdn.gettyimages.com/widgets.js");
    // const url = data.attributes.postID;
    //
    const newlink = document.createElement("a");
    newlink.classList.add("gie-single");
    newlink.setAttribute("href", "http://www.gettyimages.ca/detail/863162608");
    newlink.setAttribute("id", "ZYNytzqrQIdsHgP0qC0CMg");
    newlink.setAttribute("target", "_blank");

    element.appendChild(newlink);

    setImmediate(() => {
        void convertgettyEmbeds();
    });
}
//<a id='y4YYMji7SNlvHTA09sjd3g' class='gie-single' href='http://www.gettyimages.ca/detail/863162608' target='_blank' style='color:#a7a7a7;text-decoration:none;font-weight:normal !important;border:none;display:inline-block;'>Embed from Getty Images</a><script>window.gie=window.gie||function(c){(gie.q=gie.q||[]).push(c)};gie(function(){gie.widgets.load({id:'y4YYMji7SNlvHTA09sjd3g',sig:'s7e8baosglgGoQlntBjZ0ZpZtGKWOrHJQVpf1O0CdYw=',w:'508px',h:'339px',items:'863162608',caption: true ,tld:'ca',is360: false })});</script><script src='//embed-cdn.gettyimages.com/widgets.js' charset='utf-8' async></script>
