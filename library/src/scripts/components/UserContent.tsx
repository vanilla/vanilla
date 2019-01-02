/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import className from "classnames";
import { initAllUserContent } from "@library/user-content";

interface IProps {
    className?: string;
    scrollToOffset?: number;
    content: string;
}

/**
 * A component for placing rendered user content.
 *
 * This will ensure that all embeds/etc are initialized.
 */
export default class UserContent extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        scrollToOffset: 0,
    };

    public render() {
        return (
            <div
                className={className("userContent", this.props.className)}
                dangerouslySetInnerHTML={{ __html: this.props.content }}
            />
        );
    }

    /**
     * @inheritdoc
     */
    public componentDidMount() {
        initAllUserContent();
        this.scrollToHash();
        window.addEventListener("hashchange", this.scrollToHash);
    }

    /**
     * @inheritdoc
     */
    public componentWillUnmount() {
        window.removeEventListener("hashchange", this.scrollToHash);
    }

    /**
     * Scroll to the window's current hash value.
     */
    private scrollToHash = (event?: HashChangeEvent) => {
        event && event.preventDefault();
        const id = window.location.hash.replace("#", "");
        const element = document.querySelector(`[data-id="${id}"]`) as HTMLElement;
        if (element) {
            const top = window.pageYOffset + element.getBoundingClientRect().top + this.props.scrollToOffset!;
            window.scrollTo({ top, behavior: "smooth" });
        }
    };
}
