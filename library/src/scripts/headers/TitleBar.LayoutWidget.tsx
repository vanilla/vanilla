/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { titleBarClasses } from "@library/headers/TitleBar.classes";
import { useTitleBarParams } from "@library/headers/TitleBar.ParamContext";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { mergeRefs, useMeasure, useStatefulRef } from "@vanilla/react-utils";
import { forwardRef, useLayoutEffect, useRef } from "react";

export const TitleBarLayoutWidget = forwardRef(function TitleBarLayoutWidget(
    props: {
        children: React.ReactNode;
        className?: string;
    },
    ref: React.ForwardedRef<HTMLElement>,
) {
    const params = useTitleBarParams();
    const ownRef = useStatefulRef<HTMLDivElement | null>(null);
    const measure = useMeasure(ownRef);
    const { resetScrollOffset, setScrollOffset, offsetClass } = useScrollOffset();
    const classes = titleBarClasses.useAsHook();

    useLayoutEffect(() => {
        setScrollOffset(measure.height);
        return () => {
            resetScrollOffset();
        };
    }, [setScrollOffset, resetScrollOffset, measure.height]);

    return (
        <>
            <LayoutWidget
                ref={mergeRefs(ref, ownRef)}
                interWidgetSpacing={"none"}
                className={cx(classes.layoutWidget, offsetClass, props.className)}
            >
                {props.children}
            </LayoutWidget>
            {params.positioning === "StickyTransparent" && (
                <div
                    style={{
                        marginTop: -measure.height,
                    }}
                />
            )}
        </>
    );
});
