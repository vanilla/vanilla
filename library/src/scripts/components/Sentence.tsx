/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import classNames from "classnames";
import { Link } from "react-router-dom";
import * as React from "react";

export const enum InlineTypes {
    TEXT = "TEXT",
    LINK = "LINK",
    DATETIME = "DATETIME",
}

export interface IInlineText {
    contents: string | IWord[]; // We can nest elements
    className?: string;
    type: InlineTypes.TEXT;
}

export interface IInlineLink {
    contents: string | IWord[]; // We can nest elements
    to: string;
    className?: string;
    type: InlineTypes.LINK;
}

export interface IInlineDateTime {
    contents: string | IWord[]; // We can nest elements
    timeStamp: string;
    className?: string;
    type: InlineTypes.DATETIME;
}

// smallest element
export interface IWord {
    contents: IInlineText | IInlineLink | IInlineDateTime | IWord[] | string;
    className?: string;
    type: InlineTypes;
}

export interface ISentence {
    className?: string;
    contents: IWord[] | string;
    counter?: number;
}

/**
 * Combines multiple inline elements together. Usually for translated text with links, datetimes and text.
 * No need to set "counter". It will be set automatically. Kept optional to not need to call it on the top level. Used for React's "key" values
 */
export default class Sentence extends React.Component<ISentence> {
    public static defaultProps = {
        counter: 0,
    };

    public render() {
        const spacer = ` `;
        if (typeof this.props.contents !== "string") {
            return (this.props.contents as IWord[]).map((word: IWord, i: number) => {
                const key = "sentence-" + this.props.counter + "-" + i;
                const childCounter = this.props.counter! + 1;

                switch (word.type) {
                    case InlineTypes.DATETIME:
                        const time = word as IInlineDateTime;
                        return (
                            <time
                                className={classNames("word", "word-time", time.className)}
                                dateTime={time.timeStamp}
                                key={key}
                            >
                                <Sentence className={time.className} contents={time.contents} counter={childCounter} />
                            </time>
                        );
                    case InlineTypes.LINK:
                        const link = word as IInlineLink;
                        return (
                            <Link to={link.to} className={classNames("word", "word-link", link.className)} key={key}>
                                <Sentence className={word.className} contents={link.contents} counter={childCounter} />
                            </Link>
                        );
                    default:
                        const text = word as IInlineText;
                        return (
                            <span className={classNames("word", "word-text", word.className)} key={key}>
                                <Sentence className={word.className} contents={text.contents} counter={childCounter} />
                            </span>
                        );
                }
            });
        } else {
            return this.props.contents; // plain text
        }
    }
}
