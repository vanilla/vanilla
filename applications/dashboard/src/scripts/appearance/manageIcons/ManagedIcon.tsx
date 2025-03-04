/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IconHexGrid } from "@dashboard/appearance/manageIcons/IconHexGrid";
import type { ManageIconsApi } from "@dashboard/appearance/manageIcons/ManageIconsApi";
import { useManageIconsForm } from "@dashboard/appearance/manageIcons/ManageIconsFormContext";
import uniqueId from "lodash/uniqueId";
import { memo, useMemo } from "react";

interface IProps {
    managedIcon: ManageIconsApi.IManagedIcon;
    onClick?: () => void;
    renderTitle?: boolean;
    iconSize?: number | string;
    withGrid?: boolean;
}

export const ManagedIcon = memo(function ManagedIcon(props: IProps) {
    const { managedIcon, withGrid } = props;
    const svgContents = useIconHtmlWithoutConflictingIDs(managedIcon.svgContents);
    const color = useManageIconsForm().iconColor;

    let contents = (
        <svg
            fill="none"
            focusable="false"
            {...managedIcon.svgAttributes}
            style={{
                ...managedIcon.svgAttributes.style,
                color: color,
                height: props.iconSize ?? 48,
                width: props.iconSize ?? 48,
            }}
            dangerouslySetInnerHTML={{ __html: svgContents }}
            width={props.iconSize ?? 48}
            height={props.iconSize ?? 48}
        />
    );

    if (withGrid) {
        contents = <IconHexGrid iconSize={props.iconSize ?? 48}>{contents}</IconHexGrid>;
    }

    return contents;
});

/**
 * There can be some weirdness if multiple variations of the same SVG are present in the page and they each contain a common piece that is referenced by ID. This function will check for conflicting IDs and rename them to avoid conflicts.
 *
 * This logic shouldn't be needed during actual icon rendering since we won't be having multiple versions of the same icon existing on the page at the same time.
 */
function useIconHtmlWithoutConflictingIDs(html: string) {
    const cleaned = useMemo(() => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const elementsWithID = doc.querySelectorAll("[id]");
        if (elementsWithID.length === 0) {
            return html;
        }

        // We have IDs, let's go make sure they are don't conflict with our global icons
        const iconDefsContainer = document.querySelector("#vanilla-icon-defs");
        if (!iconDefsContainer) {
            return html;
        }

        const replacementMap: Record<string, string> = {};

        for (const element of Array.from(elementsWithID)) {
            try {
                // eslint-disable-next-line no-var
                var hasMatch = document.getElementById(element.id);
            } catch (e) {
                // Sometimes the id might not be a valid selector.
                continue;
            }
            if (hasMatch) {
                const replacementID = uniqueId("icon-piece-");
                replacementMap[element.id] = replacementID;
                element.id = replacementID;
            }
        }

        // Dump it back out
        html = doc.documentElement.innerHTML;
        for (const [oldID, newID] of Object.entries(replacementMap)) {
            html = html.replace(`url(#${oldID})`, `url(#${newID})`);
        }
        return html;
    }, [html]);

    return cleaned;
}
