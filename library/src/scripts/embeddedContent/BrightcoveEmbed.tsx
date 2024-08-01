/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBaseEmbedProps } from "@library/embeddedContent/embedService.register";
import React from "react";
import { IFrameEmbed } from "@library/embeddedContent/IFrameEmbed";

interface IProps extends IBaseEmbedProps {}

/**
 * This component will render an iframe embed for a Brightcove video with
 * a 16:9 aspect ratio.
 *
 * There appears to be no way to get the video thumbnail without
 * executing some javascript, or getting a token for the brightcove api for
 * the more efficient VideoEmbed.
 */
export function BrightcoveEmbed(props: IProps): JSX.Element {
    return (
        <>
            <IFrameEmbed style={{ aspectRatio: "16 / 9" }} embedType={props.embedType} url={props.url} />
        </>
    );
}
