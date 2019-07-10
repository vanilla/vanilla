/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import BaseEmbed from "@library/content/embeds/BaseEmbed";
import { registerEmbedComponent } from "@library/content/embeds/embedUtils";
import { sanitizeUrl } from "@vanilla/utils";

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
            <a className="embedImage-link" href={sanitizedUrl || ""} rel="nofollow noopener noreferrer" target="_blank">
                <img className="embedImage-img" src={url || ""} alt={name || ""} />
            </a>
        );
    }
}
