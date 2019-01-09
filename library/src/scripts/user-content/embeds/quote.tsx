/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";
import { registerEmbedComponent, IEmbedProps, IEmbedData, IQuoteEmbedData } from "@library/embeds";
import { onContent, t, formatUrl, makeProfileUrl } from "@library/application";
import CollapsableUserContent from "@library/user-content/CollapsableContent";
import uniqueId from "lodash/uniqueId";
import classnames from "classnames";
import api from "@library/apiv2";
import DateTime from "@library/components/DateTime";

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
                embed.classList.remove("embedResponsive-initialLink");
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

function ChevronUpIcon() {
    return (
        <svg className="icon embedQuote-chevronUp" viewBox="0 0 20 20">
            <title>{t("▲")}</title>
            <path
                fill="currentColor"
                stroke-linecap="square"
                fill-rule="evenodd"
                d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z"
                transform="rotate(-90 9.857 10.429)"
            />
        </svg>
    );
}

function ChevronDownIcon() {
    return (
        <svg className="icon embedQuote-chevronDown" viewBox="0 0 20 20">
            <title>{t("▼")}</title>
            <path
                fill="currentColor"
                stroke-linecap="square"
                fill-rule="evenodd"
                d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z"
                transform="rotate(90 9.857 10.429)"
            />
        </svg>
    );
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

        const name = (this.quoteData as any).name;

        const title = name ? (
            <h2 className="embedText-title embedQuote-title">
                <a href={this.quoteData.url} className="embedText-titleLink">
                    {name}
                </a>
            </h2>
        ) : null;

        const bodyClasses = classnames("embedText-body", "embedQuote-body", { isCollapsed: this.state.isCollapsed });
        const userUrl = makeProfileUrl(insertUser.name);

        return (
            <blockquote className={bodyClasses}>
                <div className="embedText-header embedQuote-header">
                    {title}
                    <a href={userUrl} className="embedQuote-userLink">
                        <span className="embedQuote-userPhotoWrap">
                            <img
                                src={insertUser.photoUrl}
                                alt={insertUser.name}
                                className="embedQuote-userPhoto"
                                tabIndex={-1}
                            />
                        </span>
                        <span className="embedQuote-userName">{insertUser.name}</span>
                    </a>
                    <a href={this.quoteData.url} className="embedQuote-metaLink">
                        <DateTime timestamp={this.dateTime} className="embedText-dateTime embedQuote-dateTime meta" />
                    </a>

                    {this.state.needsCollapseButton && (
                        <button
                            type="button"
                            className="embedQuote-collapseButton"
                            aria-label={t("Toggle Quote")}
                            onClick={this.toggleCollapseState}
                            aria-pressed={this.state.isCollapsed}
                        >
                            {this.state.isCollapsed ? <ChevronDownIcon /> : <ChevronUpIcon />}
                        </button>
                    )}
                </div>
                <div className="embedText-main embedQuote-main">
                    <div className="embedQuote-excerpt">
                        <CollapsableUserContent
                            setNeedsCollapser={this.setNeedsCollapser}
                            isCollapsed={this.state.isCollapsed}
                            id={id}
                            preferredMaxHeight={100}
                            dangerouslySetInnerHTML={{ __html: body ? body : this.state.renderedBody }}
                        />
                    </div>
                </div>
            </blockquote>
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
}
