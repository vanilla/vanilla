/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useCallback } from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { simplifyFraction } from "@vanilla/utils";
import { t } from "@library/utility/appUtils";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";

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
        case "16:9":
            ratioClass = "is16by9";
        case "4:3":
            ratioClass = "is4by3";
        case "1:1":
            ratioClass = "is1by1";
    }

    const style: React.CSSProperties = ratioClass
        ? {}
        : {
              paddingTop: ((height || 3) / (width || 4)) * 100 + "%",
          };

    const thumbnail = (
        <div className={`embedVideo-ratio ${ratioClass}`} style={style}>
            <button type="button" aria-label={name || undefined} className="embedVideo-playButton">
                <img
                    onClick={togglePlaying}
                    src={photoUrl || undefined}
                    role="presentation"
                    className="embedVideo-thumbnail"
                />
                <span className="embedVideo-scrim" />
                <PlayIcon />
            </button>
        </div>
    );

    const content = isPlaying ? <VideoIframe url={frameSrc} /> : thumbnail;
    return (
        <EmbedContainer className="embedVideo" inEditor={props.inEditor}>
            <EmbedContent type={embedType} inEditor={props.inEditor}>
                {content}
            </EmbedContent>
        </EmbedContainer>
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
    const style: React.CSSProperties = { fill: "currentColor", strokeWidth: 0.3 };

    return (
        <svg className="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24">
            <title>{t("Play Video")}</title>
            <path
                className="embedVideo-playIconPath embedVideo-playIconPath-circle"
                style={style}
                d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"
            />
            <polygon
                className="embedVideo-playIconPath embedVideo-playIconPath-triangle"
                style={style}
                points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"
            />
        </svg>
    );
}
