/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import BaseEmbed from "@library/content/embeds/BaseEmbed";
import { ensureScript } from "@library/dom/domUtils";
import { onContent } from "@library/utility/appUtils";
import { registerEmbedComponent } from "@library/content/embeds/embedUtils";

export function initInstagramEmbeds() {
    registerEmbedComponent("instagram", InstagramEmbed);
    onContent(convertInstagramEmbeds);
}

export class InstagramEmbed extends BaseEmbed {
    public render() {
        const { data } = this.props;
        const { attributes, url } = data;
        const { permaLink, versionNumber, isCaptioned } = attributes;
        if (!data.attributes.permaLink) {
            throw new Error("Attempted to embed a Instagram post failed link is invalid");
        }

        return (
            <blockquote
                className="instagram-media"
                data-instgrmPermalink={permaLink}
                data-instgrmVersion={versionNumber}
                data-instgrmCaptioned={isCaptioned}
            >
                <a href={url}>{url}</a>
            </blockquote>
        );
    }

    public componentDidMount() {
        void convertInstagramEmbeds().then(this.props.onRenderComplete);
    }

    public componentDidUpdate() {
        void convertInstagramEmbeds().then(this.props.onRenderComplete);
    }
}

/**
 * Renders posted instagram embeds.
 */
export async function convertInstagramEmbeds() {
    const instagramEmbeds = document.querySelectorAll(".instagram-media");
    if (instagramEmbeds.length > 0) {
        await ensureScript("//platform.instagram.com/en_US/embeds.js");

        if (!window.instgrm) {
            throw new Error("The Instagram post failed to load");
        }

        window.instgrm.Embeds.process();
    }
}
