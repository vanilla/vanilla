/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useEffect, useState } from "react";
import className from "classnames";
import { initAllUserContent } from "@library/content";
import { userContentClasses } from "@library/content/userContentStyles";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { forceRenderStyles } from "typestyle";

interface IProps {
    className?: string;
    content: string;
}

/**
 * A component for placing rendered user content.
 *
 * This will ensure that all embeds/etc are initialized.
 */
export default function UserContent(props: IProps) {
    const classes = userContentClasses();
    const offset = useScrollOffset();

    /**
     * Scroll to the window's current hash value.
     */
    const scrollToHash = useCallback((event?: HashChangeEvent) => {
        event && event.preventDefault();

        const targetID = window.location.hash.replace("#", "");
        const element = document.querySelector(`[data-id="${targetID}"]`) as HTMLElement;
        if (element) {
            forceRenderStyles();
            offset.temporarilyDisabledWatching(500);
            const top = window.pageYOffset + element.getBoundingClientRect().top - offset.getCalcedHashOffset();
            window.scrollTo({ top, behavior: "smooth" });
        }
    }, []);

    useEffect(() => {
        initAllUserContent();
    });

    useEffect(() => {
        scrollToHash();
    }, []);

    useEffect(() => {
        window.addEventListener("hashchange", scrollToHash);
        return () => {
            window.removeEventListener("hashchange", scrollToHash);
        };
    }, [scrollToHash]);

    return (
        <div
            className={className("userContent", props.className, classes.root)}
            dangerouslySetInnerHTML={{ __html: props.content }}
        />
    );
}
