/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import React from "react";

interface IProps extends IBaseEmbedProps {
    author: string;
    codePenID: string;
    height?: number;
    name?: string;
}

const MIN_HEIGHT = 350;

/**
 * A class for rendering CodePen embeds.
 */
export function CodePenEmbed(props: IProps): JSX.Element {
    const src = `https://codepen.io/${props.author}/embed/preview/${props.codePenID}`;

    return (
        <EmbedContent type={props.embedType} inEditor={props.inEditor}>
            <iframe src={src} height={Math.max(props.height || MIN_HEIGHT, MIN_HEIGHT)} width="100%" scrolling="no" />
        </EmbedContent>
    );
}
