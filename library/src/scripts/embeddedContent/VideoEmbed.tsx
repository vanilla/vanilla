/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { t } from "@library/utility/appUtils";
import { simplifyFraction } from "@vanilla/utils";
import classNames from "classnames";
import React, { useCallback, useState } from "react";
import { style } from "@library/styles/styleShim";
import { percent } from "csx";

interface IProps extends IBaseEmbedProps {
    height: number;
    width: number;
    photoUrl: string;
    frameSrc: string;
    time?: string;
}

export function VideoEmbed(props: IProps) {
    const { name, height, width, frameSrc, photoUrl, embedType } = props;

    const [isPlaying, setIsPlaying] = useState(false);

    const togglePlaying = useCallback(() => {
        setIsPlaying(!isPlaying);
    }, [setIsPlaying, isPlaying]);

    let ratioClass: string | undefined;
    const ratio = simplifyFraction(height || 3, width || 4);
    switch (ratio.shorthand) {
        case "21:9":
            ratioClass = "is21by9";
            break;
        case "4:3":
            ratioClass = "is4by3";
            break;
        case "1:1":
            ratioClass = "is1by1";
            break;
        case "16:9":
            ratioClass = "is16by9";
            break;
        default:
            ratioClass = style({
                label: "isCustomRatio",
                paddingTop: percent(((height || 3) / (width || 4)) * 100),
            });
    }

    return (
        <EmbedContainer className="embedVideo">
            <EmbedContent type={embedType}>
                <div className={classNames("embedVideo-ratio", ratioClass)}>
                    {isPlaying ? (
                        <VideoIframe url={frameSrc} />
                    ) : (
                        <VideoThumbnail name={name} onClick={togglePlaying} photoUrl={photoUrl} />
                    )}
                </div>
            </EmbedContent>
        </EmbedContainer>
    );
}

function VideoThumbnail(props: { name?: string; onClick: React.MouseEventHandler; photoUrl: string }) {
    return (
        <button type="button" aria-label={props.name} className="embedVideo-playButton" onClick={props.onClick}>
            <img src={props.photoUrl} role="presentation" className="embedVideo-thumbnail" />
            <span className="embedVideo-playIconWrap">
                <PlayIcon />
            </span>
        </button>
    );
}

function VideoIframe(props: { url: string }) {
    return (
        <iframe
            frameBorder="0"
            allow="autoplay; encrypted-media"
            className="embedVideo-iframe"
            src={props.url}
            allowFullScreen={true}
        />
    );
}

function PlayIcon() {
    const cssStyle: React.CSSProperties = { fill: "currentColor", strokeWidth: 0.3 };

    return (
        <svg className="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24">
            <title>{t("Play Video")}</title>
            <polygon
                className="embedVideo-playIconPath embedVideo-playIconPath-triangle"
                style={cssStyle}
                points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"
            />
        </svg>
    );
}
