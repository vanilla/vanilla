/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { scrollToCurrentHash, useHashScrolling } from "@library/content/hashScrolling";
import { userContentClasses } from "@library/content/UserContent.styles";
import React, { useEffect, useMemo } from "react";

import { cx } from "@emotion/css";
import { notEmpty } from "@vanilla/utils";
import { mountAllEmbeds } from "@library/embeddedContent/embedService.mounting";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { blessStringAsSanitizedHtml, type VanillaSanitizedHtml } from "@vanilla/dom-utils";

type ICommonProps = {
    className?: string;
    // Only use this if the content has been sanitized by the server, otherwise just use userContentClasses().root
    ignoreHashScrolling?: boolean;
    moderateEmbeds?: boolean;
};

type IServerSanitizedProps = ICommonProps & { vanillaSanitizedHtml: VanillaSanitizedHtml; children?: never };

type IReactElementProps = ICommonProps & {
    children: React.ReactNode;
    vanillaSanitizedHtml?: never;
};

type IUserContentProps = ICommonProps & (IServerSanitizedProps | IReactElementProps);

/**
 * A component for placing rendered user content.
 *
 * This will ensure that all embeds/etc are initialized.
 */
export default function UserContent(props: IUserContentProps) {
    if (props.vanillaSanitizedHtml) {
        return <UserContentServerContentImpl {...(props as IServerSanitizedProps)} />;
    }

    return <UserContentReactElementImpl {...(props as IReactElementProps)} />;
}

function UserContentServerContentImpl(props: IServerSanitizedProps) {
    let content = props.vanillaSanitizedHtml;
    content = useUnsafeResponsiveTableHTML(content);
    const contentWithModerationContainers = useModerateEmbeds(content);

    if (props.moderateEmbeds) {
        content = contentWithModerationContainers;
    }

    useHashScrolling(content, props.ignoreHashScrolling);

    const classes = userContentClasses.useAsHook();

    const { temporarilyDisabledWatching, getCalcedHashOffset } = useScrollOffset();
    const calcedOffset = getCalcedHashOffset();

    useEffect(() => {
        void mountAllEmbeds().then(() => {
            scrollToCurrentHash(calcedOffset);
        });
        // apply fade effect for overflowing table after mounting
        document.querySelectorAll("table").forEach((table) => {
            applyTableOverflowFade(table);
        });
    }, [content]);

    return (
        <div
            className={cx("userContent", classes.root, props.className)}
            dangerouslySetInnerHTML={{ __html: content }}
        />
    );
}

function UserContentReactElementImpl(props: IReactElementProps) {
    const { children, className } = props;

    const classes = userContentClasses.useAsHook();

    return <div className={cx("userContent", classes.root, className)}>{children}</div>;
}

/**
 * WARNING!!! Only ever use this with server-parsed trusted HTML content.
 * @param html
 */
function useUnsafeResponsiveTableHTML(html: VanillaSanitizedHtml): VanillaSanitizedHtml {
    return useMemo(() => {
        const element = document.createElement("div");
        element.innerHTML = html;

        try {
            element.querySelectorAll("table").forEach((table) => {
                // if our table is edited through rich table UI, we don't do the mobile responsify
                const shouldResponsify = !table.parentElement?.classList.contains("customized");
                if (shouldResponsify) {
                    responsifyTable(table);
                }
            });
        } catch (e) {
            console.error("Failed to responsify table", e);
            return html;
        }

        return blessStringAsSanitizedHtml(element.innerHTML);
    }, [html]);
}

function useModerateEmbeds(html: VanillaSanitizedHtml): VanillaSanitizedHtml {
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

        return blessStringAsSanitizedHtml(element.innerHTML);
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

            // If we have a very large table, the page can crash, so check the length
            // 1000 worked for https://higherlogic.atlassian.net/browse/VNLA-7722
            if (headLabels && headLabels.length < 1000) {
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

export function applyTableOverflowFade(table: HTMLTableElement) {
    const tableWrapper = table?.parentElement;

    function updateFades() {
        const scrollLeft = tableWrapper?.scrollLeft ?? 0;
        const maxScroll = (tableWrapper?.scrollWidth ?? 0) - (tableWrapper?.clientWidth ?? 0);

        if (scrollLeft > 0) {
            tableWrapper?.classList.add("hasLeftScroll");
        } else {
            tableWrapper?.classList.remove("hasLeftScroll");
        }

        if (scrollLeft + 1 < maxScroll) {
            tableWrapper?.classList.add("hasRightScroll");
        } else {
            tableWrapper?.classList.remove("hasRightScroll");
        }

        if (maxScroll <= 0) {
            tableWrapper?.classList.add("noScroll");
        } else {
            tableWrapper?.classList.remove("noScroll");
        }
    }

    if (tableWrapper?.classList.contains("customized")) {
        tableWrapper.addEventListener("scroll", updateFades);
        updateFades();
    }
}
