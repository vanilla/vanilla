/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import { QuickLinksView } from "@library/navigation/QuickLinks.view";
import { getThemeVariables } from "@library/theming/getThemeVariables";
import React, { useMemo } from "react";

interface IProps {
    title?: string;
}

export function QuickLinks(props: IProps) {
    const { links } = quickLinksVariables();
    // Get counts which are dynamically injected into the theme variables.

    const linksWithCounts = useMemo(() => {
        const counts = getThemeVariables()?.quickLinks?.counts;
        if (!counts) {
            return links;
        }
        for (const [key, value] of Object.entries(links)) {
            const count = counts[value.id] ?? null;
            if (count !== null) {
                links[key].count = count;
            }
        }
        return links;
    }, [links]);

    return <QuickLinksView title={props.title} links={linksWithCounts} />;
}
