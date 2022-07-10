/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ensureScript } from "@vanilla/dom-utils";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import React, { useLayoutEffect } from "react";
import { useThrowError } from "@vanilla/react-utils";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";

interface IProps extends IBaseEmbedProps {
    sessionId: string;
    domain: string;
}

const PANOPTO_SCRIPT: string = "https://developers.panopto.com/scripts/embedapi.min.js";

const EMBED_LOADED_CLASS: string = "isLoaded";

/**
 * A class for rendering Twitter embeds.
 */
export function PanoptoEmbed(props: IProps): JSX.Element {
    const throwError = useThrowError();
    const playerId = "player-" + Math.random().toString(36).substr(2, 9);

    useLayoutEffect(() => {
        void convertPanoptoEmbeds().catch(throwError);
    });

    // Ratio is hardcoded at 16:9. Panopto is not returning the dimensions.
    return (
        <>
            <EmbedContainer className="embedVideo">
                <EmbedContent type={props.embedType}>
                    <div
                        id={playerId}
                        className="panoptoVideo embedVideo-ratio is16by9"
                        data-domain={props.domain}
                        data-playerid={playerId}
                        data-sessionid={props.sessionId}
                        data-url={props.url}
                    ></div>
                </EmbedContent>
            </EmbedContainer>
        </>
    );
}

/**
 * Convert all of the Panopto embeds in the page.
 */
async function convertPanoptoEmbeds() {
    const panoptoEmbeds = Array.from(document.querySelectorAll(".panoptoVideo"));

    if (panoptoEmbeds.length > 0) {
        await ensureScript(PANOPTO_SCRIPT);
        panoptoEmbeds.map((contentElement) => {
            // Only render the embed if its not loaded yet.
            if (!contentElement.classList.contains(EMBED_LOADED_CLASS)) {
                renderPanoptoEmbed(contentElement as HTMLElement);
            }
        });
    }
}

/**
 * Render a single Panopto embed.
 */
async function renderPanoptoEmbed(element: HTMLElement) {
    const sessionId = element.getAttribute("data-sessionid");
    if (sessionId == null) {
        throw new Error("Attempted to embed a Panopto video but the sessionId could not be found.");
    }

    const playerId = element.getAttribute("data-playerid");
    if (playerId == null) {
        throw new Error("Attempted to embed a Panopto video but the playerId is missing.");
    }

    const domain = element.getAttribute("data-domain");
    if (domain == null) {
        throw new Error("Attempted to embed a Panopto video but the domain could not be found.");
    }

    if (window.EmbedApi === undefined) {
        throw new Error("Attempted to embed a Panopto but an error has occurred.");
    }

    new window.EmbedApi(playerId, {
        serverName: domain,
        sessionId: sessionId,
    });

    element.classList.add(EMBED_LOADED_CLASS);

    // Add embedVideo-iframe class for proper dimensions.
    let iframe: HTMLElement | undefined = element.getElementsByTagName("iframe")[0];
    if (iframe != undefined) {
        iframe.classList.add("embedVideo-iframe");
    }
}
