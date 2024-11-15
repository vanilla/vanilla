/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useHashScrolling } from "@library/content/hashScrolling";
import { userContentClasses } from "@library/content/UserContent.styles";
import React, { useEffect, useMemo } from "react";

import { cx } from "@emotion/css";
import { notEmpty } from "@vanilla/utils";
import { mountAllEmbeds } from "@library/embeddedContent/embedService.mounting";

interface IProps {
    className?: string;
    content: string;
    ignoreHashScrolling?: boolean;
    moderateEmbeds?: boolean;
}

/**
 * A component for placing rendered user content.
 *
 * This will ensure that all embeds/etc are initialized.
 */
export default function UserContent(props: IProps) {
    let content = props.content;
    content = useUnsafeResponsiveTableHTML(content);
    const contentWithModerationContainers = useModerateEmbeds(content);

    if (props.moderateEmbeds) {
        content = contentWithModerationContainers;
    }

    useHashScrolling(content, props.ignoreHashScrolling);

    const classes = userContentClasses();

    useEffect(() => {
        mountAllEmbeds();
    }, [content]);

    return (
        <div
            className={cx("userContent", classes.root, props.className)}
            dangerouslySetInnerHTML={{ __html: content }}
        />
    );
}

/**
 * WARNING!!! Only ever use this with server-parsed trusted HTML content.
 * @param html
 */
function useUnsafeResponsiveTableHTML(html: string) {
    return useMemo(() => {
        const element = document.createElement("div");
        element.innerHTML = html;

        try {
            element.querySelectorAll("table").forEach(responsifyTable);
        } catch (e) {
            console.error("Failed to responsify table", e);
            return element.innerHTML;
        }

        return element.innerHTML;
    }, [html]);
}

function useModerateEmbeds(html: string) {
    return useMemo(() => {
        const element = document.createElement("div");
        element.innerHTML = html;

        const embedsArray = Array.from(element.getElementsByClassName("embedImage-link")).concat(
            Array.from(element.getElementsByClassName("js-embed")),
        );

        embedsArray.forEach((embed) => {
            const outerContainer = document.createElement("div");
            outerContainer.classList.add("moderationImageAndButtonContainer");

            const moderationContainer = document.createElement("div");
            moderationContainer.classList.add("moderationContainer");
            moderationContainer.classList.add("blur");

            // wrap the image/embed in the container which will be blurred
            embed.parentNode?.insertBefore(moderationContainer, embed);
            moderationContainer.appendChild(embed);

            // Wrap button and embeded image in outer container
            moderationContainer.parentNode?.insertBefore(outerContainer, moderationContainer);
            outerContainer.appendChild(moderationContainer);
        });

        return element.innerHTML;
    }, [html]);
}

export function responsifyTable(table: HTMLTableElement) {
    let head: HTMLElement | null = table.querySelector("thead");
    if (!head) {
        // Check if our first body row is all ths
        const firstRow = table.querySelector("tr");
        let isAllTh = true;
        if (firstRow) {
            Array.from(firstRow?.children).forEach((child) => {
                if (child.tagName !== "TH") {
                    isAllTh = false;
                }
            });
        }

        if (isAllTh) {
            head = firstRow;
        }
    }

    let headLabels: string[] | null = null;

    if (head) {
        head.classList.add("tableHead");
        // Apply labels for each table cell.
        headLabels = Array.from(head.querySelectorAll("th"))
            .map((th) => th.textContent ?? null)
            .filter(notEmpty);
        head.querySelectorAll("th").forEach((th) => th.setAttribute("scope", "col"));
    }

    const rows = table.querySelectorAll("tbody tr");
    rows.forEach((tr) => {
        // Apply a scope on existing first ths.
        const firstTh = tr.querySelector("th");
        if (firstTh) {
            firstTh.setAttribute("scope", "row");
        }

        const cells = tr.querySelectorAll("td, th");
        cells.forEach((td, i) => {
            const mobileTh = document.createElement("th");
            if (headLabels) {
                // Apply extra labels throughout the table.
                const label = headLabels[i] ?? "";
                mobileTh.textContent = label;
                mobileTh.classList.add("mobileTableHead");
                mobileTh.setAttribute("aria-hidden", "true");
                td.insertAdjacentElement("beforebegin", mobileTh);
            }

            if (i % 2 === 1) {
                // Apply a class for mobile stripes.
                td.classList.add("mobileStripe");
                mobileTh.classList.add("mobileStripe");
            }
        });
    });

    // Wrap in a responsive table class.
    if (table.parentNode) {
        // create wrapper container
        const wrapper = document.createElement("div");
        wrapper.classList.add("tableWrapper");

        // insert wrapper before table in the DOM tree
        table.parentNode.insertBefore(wrapper, table);

        // move table into wrapper
        wrapper.appendChild(table);
    }
}
