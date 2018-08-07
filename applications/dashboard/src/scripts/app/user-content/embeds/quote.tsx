/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import { registerEmbedComponent, IEmbedProps, IEmbedData, IQuoteEmbedData } from "@dashboard/embeds";
import { onContent, t } from "@dashboard/application";
import CollapsableUserContent from "@dashboard/app/user-content/collapsableContent";
import uniqueId from "lodash/uniqueId";
import classnames from "classnames";
import api from "@dashboard/apiv2";

export function initQuoteEmbeds() {
    registerEmbedComponent("quote", QuoteEmbed as any);
    onContent(mountQuoteEmbeds);
}

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

export class QuoteEmbed extends React.Component<IEmbedProps<IEmbedData>, IState> {
    public state: IState = {
        isCollapsed: true,
        renderedBody: "",
        needsCollapseButton: false,
    };

    public render() {
        const { body, insertUser } = this.quoteData;
        const id = uniqueId("collapsableContent-");

        const title =
            "name" in this.props.data ? (
                <h3 className="embedText-title embedQuote-title">{this.props.data.name}</h3>
            ) : null;

        const bodyClasses = classnames("embedText-body", "embedQuote-body", { isCollapsed: this.state.isCollapsed });
        const collapseIconClasses = classnames("icon", "embedQuote-collapseButton", "icon-chevron-down");

        return (
            <article className={bodyClasses}>
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
                        {this.state.needsCollapseButton && (
                            <label className={collapseIconClasses}>
                                <span className="sr-only">{t("Collapse this quote")}</span>
                                <input
                                    type="checkbox"
                                    className="sr-only"
                                    onChange={this.toggleCollapseState}
                                    checked={this.state.isCollapsed}
                                />
                            </label>
                        )}
                    </div>
                    <div className="embedQuote-excerpt userContent">
                        <CollapsableUserContent
                            setNeedsCollapser={this.setNeedsCollapser}
                            isCollapsed={this.state.isCollapsed}
                            id={id}
                            dangerouslySetInnerHTML={{ __html: body ? body : this.state.renderedBody }}
                        />
                    </div>
                </div>
            </article>
        );
    }

    public componentDidMount() {
        if (this.quoteData.body) {
            this.props.onRenderComplete();
        } else {
            const body =
                this.quoteData.format === "Rich" ? JSON.stringify(this.quoteData.bodyRaw) : this.quoteData.bodyRaw;
            api.post("/rich/quote", {
                body,
                format: this.quoteData.format,
            }).then(response => {
                this.setState({ renderedBody: response.data.quote });
                this.props.onRenderComplete();
            });
        }
    }

    private setNeedsCollapser = needsCollapser => {
        this.setState({ needsCollapseButton: needsCollapser });
    };

    private toggleCollapseState = (event: React.ChangeEvent<HTMLInputElement>) => {
        const target = event.target;
        const value = target.type === "checkbox" ? target.checked : target.value;
        this.setState({ isCollapsed: !!value });
    };

    private get quoteData(): IQuoteEmbedData {
        return this.props.data.attributes as IQuoteEmbedData;
    }

    private get dateTime(): string {
        return this.quoteData.dateUpdated || this.quoteData.dateInserted;
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
