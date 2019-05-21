/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import BaseEmbed from "@library/content/embeds/BaseEmbed";
import { registerEmbedComponent } from "@library/content/embeds/embedUtils";

export function initCodePenEmbeds() {
    registerEmbedComponent("codepen", CodePenEmbed);
}

export class CodePenEmbed extends BaseEmbed {
    public render() {
        const { attributes, height } = this.props.data;
        const { id, embedUrl, style } = attributes;

        return <iframe id={id} src={embedUrl} height={height || 300} style={style} scrolling="no" />;
    }
}
