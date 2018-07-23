/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import BaseEmbed from "@dashboard/app/user-content/embeds/BaseEmbed";
import { registerEmbedComponent } from "@dashboard/embeds";
import { sanitizeUrl } from "@dashboard/utility";

export function initImageEmbeds() {
    registerEmbedComponent("image", ImageEmbed);
}

export class ImageEmbed extends BaseEmbed {
    public render() {
        const { data } = this.props;
        const { url, name } = data;
        const sanitizedUrl = sanitizeUrl(url);

        // Yes we actually want a target blank here (even if we don't want it on normal links).
        return (
            <a className="embedImage-link" href={sanitizedUrl || ""} rel="nofollow noopener" target="_blank">
                <img className="embedImage-img" src={url || ""} alt={name || ""} crossOrigin="anonymous" />
            </a>
        );
    }
}
