/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import { registerEmbedComponent, IEmbedProps, IEmbedData, IQuoteEmbedData } from "@dashboard/embeds";
import { onContent, t, formatUrl, makeProfileUrl } from "@dashboard/application";
import CollapsableUserContent from "@dashboard/app/user-content/CollapsableContent";
import uniqueId from "lodash/uniqueId";
import classnames from "classnames";
import api from "@dashboard/apiv2";

export function initQuoteEmbeds() {
    registerEmbedComponent("quote", QuoteEmbed as any);
    onContent(mountQuoteEmbeds);
}

/**
 * Mount all of the existing quote embeds in the page.
 *
 * Data (including server rendered HTML content should be coming down in JSON encoded attribute data-json).
 */
export function mountQuoteEmbeds() {
    const embeds = document.querySelectorAll(".js-quoteEmbed");
    for (const embed of embeds) {
        const data = embed.getAttribute("data-json");
        if (data) {
            const quoteData = JSON.parse(data) as IEmbedData;
            const onRenderComplete = () => {
                embed.removeAttribute("data-json");
            };
            ReactDOM.render(
                <QuoteEmbed data={quoteData} inEditor={false} onRenderComplete={onRenderComplete} />,
                embed,
            );
        }
    }
}

interface IState {
    isCollapsed: boolean;
    needsCollapseButton: boolean;
    renderedBody: string;
}

/**
 * An embed class for quoted user content on the same site.
 *
 * This is not an editable quote. Instead it an expandable/collapsable snapshot of the quoted/embedded comment/discussion.
 *
 * This can either recieve the post format and body (when created directly in the editor) or be given the fully rendered content (when mounting on top of existing server rendered DOM stuff).
 */
export class QuoteEmbed extends React.Component<IEmbedProps<IEmbedData>, IState> {
    public state: IState = {
        isCollapsed: true,
        renderedBody: "",
        needsCollapseButton: false,
    };

    public render() {
        const { body, insertUser } = this.quoteData;
        const id = uniqueId("collapsableContent-");

        const title = this.props.data.name ? (
            <h3 className="embedText-title embedQuote-title">{this.props.data.name}</h3>
        ) : null;

        const bodyClasses = classnames("embedText-body", "embedQuote-body", { isCollapsed: this.state.isCollapsed });
        const collapseIconClasses = classnames("icon", "embedQuote-collapseButton", "icon-chevron-up");
        const userUrl = makeProfileUrl(insertUser.name);

        return (
            <article className={bodyClasses}>
                <div className="embedText-main embedQuote-main">
                    <div className="embedText-header embedQuote-header">
                        {title}
                        <a href={userUrl} className="embedQuote-userLink">
                            <span className="embedQuote-userPhoto PhotoWrap">
                                <img
                                    src={insertUser.photoUrl}
                                    alt={insertUser.name}
                                    className="ProfilePhoto ProfilePhotoSmall"
                                    tabIndex={-1}
                                />
                            </span>
                            <span className="embedQuote-userName">{insertUser.name}</span>
                        </a>
                        <time
                            className="embedText-dateTime embedQuote-dateTime meta"
                            dateTime={this.dateTime}
                            title={this.titleTime}
                        >
                            {this.humanTime}
                        </time>
                        {this.state.needsCollapseButton && (
                            <label className={collapseIconClasses}>
                                <span className="sr-only">{t("Collapse this quote")}</span>
                                <input
                                    type="button"
                                    className="sr-only"
                                    onClick={this.toggleCollapseState}
                                    aria-pressed={this.state.isCollapsed}
                                />
                            </label>
                        )}
                    </div>
                    <div className="embedQuote-excerpt userContent">
                        <CollapsableUserContent
                            setNeedsCollapser={this.setNeedsCollapser}
                            isCollapsed={this.state.isCollapsed}
                            id={id}
                            preferredMaxHeight={100}
                            dangerouslySetInnerHTML={{ __html: body ? body : this.state.renderedBody }}
                        />
                    </div>
                </div>
            </article>
        );
    }

    /**
     * When the component mounts we need to ensure we have rendered post content.
     *
     * Either we were passed the content, or we need to make an API call to render it.
     */
    public componentDidMount() {
        if (this.quoteData.body) {
            this.props.onRenderComplete();
        } else {
            const body =
                this.quoteData.format === "Rich" ? JSON.stringify(this.quoteData.bodyRaw) : this.quoteData.bodyRaw;
            void api
                .post("/rich/quote", {
                    body,
                    format: this.quoteData.format,
                })
                .then(response => {
                    this.setState({ renderedBody: response.data.quote }, this.props.onRenderComplete);
                });
        }
    }

    /**
     * Callback for the collapser to determine if we need to show the collapse toggle or not.
     */
    private setNeedsCollapser = needsCollapser => {
        this.setState({ needsCollapseButton: needsCollapser });
    };

    /**
     * Toggle the state of whether or not we are collapsed.
     */
    private toggleCollapseState = (event: React.ChangeEvent<any>) => {
        event.preventDefault();
        this.setState({ isCollapsed: !this.state.isCollapsed });
    };

    /**
     * Get the quote embed data out of the scrape data.
     */
    private get quoteData(): IQuoteEmbedData {
        return this.props.data.attributes as IQuoteEmbedData;
    }

    /**
     * Get the timestamp to display.
     */
    private get dateTime(): string {
        return this.quoteData.dateUpdated || this.quoteData.dateInserted;
    }

    /**
     * Get the title of the time tag (long extended date)
     */
    private get titleTime(): string {
        const date = new Date(this.dateTime);
        return date.toLocaleString(
            undefined,
            { year: "numeric", month: "long", day: "numeric", weekday: "long", hour: "numeric", minute: "numeric" },
        );
    }

    /**
     * Get a shorter human readable time for the time tag.
     */
    private get humanTime(): string {
        const date = new Date(this.dateTime);
        return date.toLocaleString(
            undefined,
            { year: "numeric", month: "short", day: "numeric" },
        );
    }
}
