/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import BaseEmbed from "@library/content/embeds/BaseEmbed";
import { IEmbedProps, registerEmbedComponent } from "@library/content/embeds/embedUtils";
import { simplifyFraction } from "@vanilla/utils";
import { t } from "@library/utility/appUtils";
import { delegateEvent } from "@library/dom/domUtils";
import ReactDOM from "react-dom";

export function initVideoEmbeds() {
    registerEmbedComponent("youtube", VideoEmbed);
    registerEmbedComponent("vimeo", VideoEmbed);
    registerEmbedComponent("twitch", VideoEmbed);
    registerEmbedComponent("wistia", VideoEmbed);
    delegateEvent("click", ".js-playVideo", handlePlayVideo);
}

interface IState {
    isPlaying: boolean;
}

export class VideoEmbed extends BaseEmbed<IEmbedProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            isPlaying: false,
        };
    }
    public render() {
        const { name, height, width, attributes, photoUrl } = this.props.data;
        const { embedUrl } = attributes;

        const ratioClass = this.ratioClass;
        const style: React.CSSProperties = ratioClass
            ? {}
            : {
                  paddingTop: ((height || 3) / (width || 4)) * 100 + "%",
              };

        const thumbnail = (
            <div className={`embedVideo-ratio ${ratioClass}`} style={style}>
                <button
                    type="button"
                    data-url={embedUrl}
                    aria-label={name || undefined}
                    className="embedVideo-playButton"
                >
                    <img
                        onClick={this.clickHandler}
                        src={photoUrl || undefined}
                        role="presentation"
                        className="embedVideo-thumbnail"
                    />
                    <span className="embedVideo-scrim" />
                    <PlayIcon />
                </button>
            </div>
        );

        return this.state.isPlaying ? <VideoIframe url={embedUrl} /> : thumbnail;
    }

    private clickHandler = () => {
        this.setState({ isPlaying: true });
    };

    private get ratioClass(): string | undefined {
        const { height, width } = this.props.data;
        const ratio = simplifyFraction(height || 3, width || 4);
        switch (ratio.shorthand) {
            case "21:9":
                return "is21by9";
            case "16:9":
                return "is16by9";
            case "4:3":
                return "is4by3";
            case "1:1":
                return "is1by1";
        }
    }
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

/**
 * Handle a click on a video.
 */
function handlePlayVideo(event: MouseEvent, triggeringElement: HTMLElement) {
    // Inside of delegate event `this` is the current target of the event.
    const playButton: HTMLElement = triggeringElement;
    const container = playButton.closest(".embedVideo-ratio");
    if (container) {
        const url = playButton.dataset.url as string;

        ReactDOM.render(<VideoIframe url={url} />, container);
    }
}
