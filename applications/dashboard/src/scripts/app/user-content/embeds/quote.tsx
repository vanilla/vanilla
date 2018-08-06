/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { registerEmbedComponent, IEmbedProps, IQuoteEmbedData } from "@dashboard/embeds";

export function initQuoteEmbeds() {
    registerEmbedComponent("quote", QuoteEmbed as any);
}

export class QuoteEmbed extends React.Component<IEmbedProps<IQuoteEmbedData>> {
    public render() {
        const { body, insertUser } = this.props.data;

        const title =
            "name" in this.props.data ? (
                <h3 className="embedText-title embedQuote-title">{this.props.data.name}</h3>
            ) : null;
        return (
            <article className="embedText-body embedQuote-body">
                <div className="embedText-main embedQuote-main">
                    <div className="embedText-header embedQuote-header">
                        {title}
                        <span className="embedQuote-userPhoto PhotoWrap">
                            <img
                                src={insertUser.photoUrl}
                                alt={insertUser.name}
                                className="ProfilePhoto ProfilePhotoSmall"
                                tabIndex={-1}
                            />
                        </span>
                        <span className="embedQuote-userName">{insertUser.name}</span>
                        <time
                            className="embedText-dateTime embedQuote-dateTime meta"
                            dateTime={this.dateTime}
                            title={this.titleTime}
                        >
                            {this.humanTime}
                        </time>
                    </div>
                    <div className="embedQuote-excerpt userContent" dangerouslySetInnerHTML={{ __html: body }} />
                </div>
            </article>
        );
    }

    public componentDidMount() {
        this.props.onRenderComplete();
    }

    private get dateTime(): string {
        return this.props.data.dateUpdated || this.props.data.dateInserted;
    }

    private get titleTime(): string {
        const date = new Date(this.dateTime);
        return date.toLocaleString(
            undefined,
            { year: "numeric", month: "long", day: "numeric", weekday: "long", hour: "numeric", minute: "numeric" },
        );
    }

    private get humanTime(): string {
        const date = new Date(this.dateTime);
        return date.toLocaleString(
            undefined,
            { year: "numeric", month: "short", day: "numeric" },
        );
    }
}
