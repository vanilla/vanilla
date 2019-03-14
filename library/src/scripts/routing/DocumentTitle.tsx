/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getMeta } from "@library/utility/appUtils";

/**
 * A component for displaying and setting the document title.
 *
 * This component can render a default title or a custom title depending on the usage.
 *
 * @example <caption>Render the title in the default h1</caption>
 * <DocumentTitle title="Page Title" />
 *
 * @example <caption>Render a custom title</caption>
 * <DocumentTitle title="Title Bar Title >
 *     <h1>Page Title</h1>
 * </DocumentTitle>
 */
export default class DocumentTitle extends React.Component<IProps> {
    public componentDidMount() {
        document.title = this.getHeadTitle(this.props);
    }

    public componentWillUpdate(nextProps: IProps) {
        document.title = this.getHeadTitle(nextProps);
    }

    public render() {
        if (this.props.children) {
            return this.props.children;
        } else {
            return <h1>{this.props.title}</h1>;
        }
    }

    /**
     * Calculate the status bar title from the props.
     *
     * @param props - The props used to calculate the title.
     * @returns Returns the title as a string.
     */
    private getHeadTitle(props: IProps): string {
        const siteTitle: string = getMeta("ui.siteName", "");
        const parts: string[] = [];

        if (props.title && props.title.length > 0) {
            parts.push(props.title);
        }
        if (siteTitle.length > 0 && siteTitle !== props.title) {
            parts.push(siteTitle);
        }

        return parts.join(" - ");
    }
}

interface IProps {
    title: string;
    children?: React.ReactNode;
}
