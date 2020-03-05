/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";

interface IProps extends IBaseEmbedProps {
    giphyID: string;
    height: number;
    width: number;
}

/**
 * A class for rendering Giphy embeds.
 */
export function GiphyEmbed(props: IProps): JSX.Element {
    const paddingBottom = ((props.height || 1) / (props.width || 1)) * 100 + "%";
    const ratioStyle: React.CSSProperties = {
        maxWidth: props.width ? props.width + "px" : "100%",
        paddingBottom,
    };

    if (!props.giphyID) {
        throw new Error("Giphy embed fail, the post could not be found");
    }
    const src = `https://giphy.com/embed/${props.giphyID}`;

    return (
        <EmbedContainer className="embedGiphy">
            <EmbedContent type={props.embedType}>
                <div className="embedExternal-ratio" style={ratioStyle}>
                    <iframe src={src} className="giphy-embed embedGiphy-iframe" />
                </div>
            </EmbedContent>
        </EmbedContainer>
    );
}
