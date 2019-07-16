/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useMemo } from "react";
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
export default function DocumentTitle(props: IProps) {
    /**
     * Calculate the status bar title from the props.
     *
     * @param props - The props used to calculate the title.
     * @returns Returns the title as a string.
     */
    const headTitle = useMemo(() => {
        const siteTitle: string = getMeta("ui.siteName", "");
        const parts: string[] = [];

        if (props.title && props.title.length > 0) {
            parts.push(props.title);
        }
        if (siteTitle.length > 0 && siteTitle !== props.title) {
            parts.push(siteTitle);
        }

        return parts.join(" - ");
    }, [props.title]);

    useEffect(() => {
        document.title = headTitle;
    }, [headTitle]);

    if (props.children) {
        return props.children;
    } else {
        return <h1>{props.title}</h1>;
    }
}

interface IProps {
    title: string;
    children?: React.ReactNode;
}
