/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import BaseEmbed from "@dashboard/app/user-content/embeds/BaseEmbed";
import { ensureScript } from "@dashboard/dom";
import { onContent } from "@dashboard/application";
import { registerEmbedComponent } from "@dashboard/embeds";

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
