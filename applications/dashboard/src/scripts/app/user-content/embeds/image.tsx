/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import BaseEmbed from "@dashboard/app/user-content/embeds/BaseEmbed";
import { registerEmbedComponent } from "@dashboard/embeds";

export function initImageEmbeds() {
    registerEmbedComponent("image", ImageEmbed);
}

export class ImageEmbed extends BaseEmbed {
    public render() {
        const { data } = this.props;
        const { url, name } = data;

        return <img className="embedImage-img" src={url || ""} alt={name || ""} />;
    }
}
