/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IEmbedProps } from "@library/embeds";

/**
 * A base embed react component class.
 * @see {registerEmbedComponent}
 */
export default abstract class BaseEmbed<P extends IEmbedProps = IEmbedProps, S = {}> extends React.Component<P, S> {
    public componentDidMount() {
        this.props.onRenderComplete();
    }

    public abstract render();
}
