/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import { Link } from "react-router-dom";
import * as React from "react";

export enum InlineTypes {
    TEXT = "TEXT",
    LINK = "LINK",
    DATETIME = "DATETIME",
}

export interface IInlineText {
    children: string | IWord[]; // We can nest elements
    className?: string;
    type: InlineTypes.TEXT;
}

export interface IInlineLink {
    children: string | IWord[]; // We can nest elements
    to: string;
    className?: string;
    type: InlineTypes.LINK;
}

export interface IInlineDateTime {
    children: string | IWord[]; // We can nest elements
    timeStamp: string;
    className?: string;
    type: InlineTypes.DATETIME;
}

// smallest element
export interface IWord {
    children: IInlineText | IInlineLink | IInlineDateTime | IWord[] | string;
    className?: string;
    type: InlineTypes;
}

export interface ISentence {
    className?: string;
    children: IWord[] | string;
    counter?: number;
    recursiveChildClass?: string; // Applied to all children, including self
    descendantChildClasses?: string; // applied recursively to children (Excluding self)
    directChildClass?: string; // Applied to children only
}

/**
 * Combines multiple inline elements together. Usually for translated text with links, datetimes and text.
 * No need to set "counter". It will be set automatically. Kept optional to not need to call it on the top level. Used for React's "key" values
 */
export default class Sentence extends React.Component<ISentence> {
    public static defaultProps = {
        counter: 0,
    };

    public processChild(word, i = 1) {
        const key = "sentence-" + this.props.counter + "-" + i;
        const childCounter = this.props.counter! + 1;

        switch (word.type) {
            case InlineTypes.DATETIME:
                const time = word as IInlineDateTime;
                return (
                    <time
                        className={classNames(
                            "word",
                            "word-time",
                            time.className,
                            this.props.className,
                            this.props.recursiveChildClass,
                            this.props.directChildClass,
                        )}
                        dateTime={time.timeStamp}
                        key={key}
                    >
                        <Sentence
                            className={time.className}
                            children={time.children}
                            counter={childCounter}
                            recursiveChildClass={classNames(
                                this.props.recursiveChildClass,
                                this.props.descendantChildClasses,
                            )}
                        />
                    </time>
                );
            case InlineTypes.LINK:
                const link = word as IInlineLink;
                return (
                    <Link
                        to={link.to}
                        className={classNames(
                            "word",
                            "word-link",
                            link.className,
                            this.props.className,
                            this.props.recursiveChildClass,
                            this.props.directChildClass,
                        )}
                        key={key}
                    >
                        <Sentence
                            className={word.className}
                            children={link.children}
                            counter={childCounter}
                            recursiveChildClass={classNames(
                                this.props.recursiveChildClass,
                                this.props.descendantChildClasses,
                            )}
                        />
                    </Link>
                );
            default:
                const text = word as IInlineText;
                return (
                    <span
                        className={classNames(
                            "word",
                            "word-text",
                            word.className,
                            this.props.recursiveChildClass,
                            this.props.directChildClass,
                            this.props.className,
                        )}
                        key={key}
                    >
                        <Sentence
                            className={word.className}
                            children={text.children}
                            counter={childCounter}
                            recursiveChildClass={classNames(
                                this.props.recursiveChildClass,
                                this.props.descendantChildClasses,
                            )}
                        />
                    </span>
                );
        }
    }

    public render() {
        const spacer = ` `;
        if (typeof this.props.children !== "string") {
            if (this.props.children.length > 0) {
                return (this.props.children as IWord[]).map((word: IWord, i: number) => {
                    return this.processChild(word, i);
                });
            } else {
                return this.processChild(this.props.children);
            }
        } else {
            return this.props.children; // plain text
        }
    }
}
