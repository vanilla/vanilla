/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ensureScript } from "@vanilla/dom-utils";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import React, { useEffect } from "react";

interface IProps extends IBaseEmbedProps {
    height: number;
    width: number;
    name?: string;
    photoUrl?: string;
    photoID: string;
    foreignID: string;
    embedSignature: string;
}

/**
 * A class for rendering Getty Images embeds.
 */
export function GettyImagesEmbed(props: IProps): JSX.Element {
    useEffect(() => {
        void convertGettyEmbeds();
    });

    return (
        <EmbedContent type={props.embedType} inEditor={props.inEditor}>
            <a
                className="embedExternal-content gie-single js-gettyEmbed"
                href={`https://www.gettyimages.com/detail/${props.photoID}`}
                id={props.foreignID}
                data-height={props.height}
                data-width={props.width}
                data-sig={props.embedSignature}
                data-items={props.photoID}
            >
                {props.url}
            </a>
        </EmbedContent>
    );
}

async function convertGettyEmbeds() {
    const gettyPosts = document.querySelectorAll(".js-gettyEmbed");
    if (gettyPosts.length > 0) {
        for (const post of gettyPosts) {
            const url = post.getAttribute("href") || "";
            const foreignID = post.getAttribute("id") || "";
            const embedSignature = post.getAttribute("data-sig") || "";
            const height = Number(post.getAttribute("data-height")) || 1;
            const width = Number(post.getAttribute("data-width")) || 1;
            const items = post.getAttribute("data-items") || "";
            const data: IProps = {
                embedType: "gettyimages",
                url,
                height,
                width,
                foreignID,
                embedSignature,
                photoID: items,
            };
            await loadGettyImages(data);
            post.classList.remove("js-gettyEmbed");
        }
    }
}

async function loadGettyImages(props: IProps) {
    const fallbackCallback = (c: any) => {
        (window.gie.q = window.gie.q || []).push(c);
    };
    // This is part of Getty's embed code, we need to ensure embeds are loaded and sent to their widget.js script.
    window.gie = window.gie || fallbackCallback;

    window.gie(() => {
        window.gie.widgets.load({
            id: props.foreignID,
            sig: props.embedSignature,
            w: props.width + "px",
            h: props.height + "px",
            items: props.photoID,
            caption: false,
            is360: false,
            tld: "com",
        });
    });

    /// DO NOT IGNORE
    /// This will turn totally sideways if window.gie is not populated before the script is initially loaded.
    await ensureScript("https://embed-cdn.gettyimages.com/widgets.js");
}
