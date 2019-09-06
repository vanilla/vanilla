/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { EmbedRenderError } from "@library/embeddedContent/EmbedRenderError";

interface IProps {
    url: string;
    children: React.ReactNode;
}

interface IState {
    error: Error | null;
}

/**
 * Error boundary for catching rendering errors from embeds.
 */
export class EmbedErrorBoundary extends React.PureComponent<IProps, IState> {
    public state: IState = {
        error: null,
    };

    public render() {
        if (this.state.error) {
            return <EmbedRenderError url={this.props.url} />;
        } else {
            return this.props.children;
        }
    }

    public componentDidCatch(error: Error) {
        this.setState({ error });
    }
}
