/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useHashScrolling } from "@library/content/hashScrolling";
import { userContentClasses } from "@library/content/userContentStyles";
// import classNames from "classnames";
import React, { useMemo } from "react";

import { cx } from "@library/styles/styleShim";

interface IProps {
    className?: string;
    content: string;
    ignoreHashScrolling?: boolean;
}

/**
 * A component for placing rendered user content.
 *
 * This will ensure that all embeds/etc are initialized.
 */
export default function UserContent(props: IProps) {
    let content = props.content;
    content = useUnsafeResponsiveTableHTML(content);
    useHashScrolling(content, props.ignoreHashScrolling);

    const classes = userContentClasses();

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

        element.querySelectorAll("table").forEach(responsifyTable);

        return element.innerHTML;
    }, [html]);
}

function responsifyTable(table: HTMLTableElement) {
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
        headLabels = Array.from(head.querySelectorAll("th")).map((th) => th.innerText);
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
                tr.insertBefore(mobileTh, td);
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
