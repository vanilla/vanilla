/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import className from "classnames";
import { initAllUserContent } from "../user-content/index";

interface IUserContent {
    className?: string;
    content: string;
}

/**
 * A component for placing rendered user content.
 *
 * This will ensure that all embeds/etc are initialized.
 */
export default class UserContent extends React.Component<IUserContent> {
    public render() {
        return (
            <div
                className={className("userContent", this.props.className)}
                dangerouslySetInnerHTML={{ __html: this.props.content }}
            />
        );
    }

    public componentDidMount() {
        initAllUserContent();
    }
}
