/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";

interface IProps extends IBaseEmbedProps {
    height: number;
    width: number;
}

let _supportFrames = false;

export function supportsFrames(newValue?: boolean): boolean {
    if (newValue != null) {
        _supportFrames = newValue;
    }
    return _supportFrames;
}

/**
 * A class for rendering Giphy embeds.
 */
export function IFrameEmbed(props: IProps): JSX.Element {
    const paddingBottom = ((props.height || 1) / (props.width || 1)) * 100 + "%";
    const ratioStyle: React.CSSProperties = {
        maxWidth: props.width ? props.width + "px" : "100%",
        paddingBottom,
    };

    return (
        <EmbedContainer className="embedIFrame">
            <EmbedContent type={props.embedType}>
                <div className="embedExternal-ratio" style={ratioStyle}>
                    <iframe src={props.url} className="embedIFrame-iframe" frameBorder={0} />
                </div>
            </EmbedContent>
        </EmbedContainer>
    );
}
