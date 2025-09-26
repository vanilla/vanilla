/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Container from "@library/layout/components/Container";
import { forwardRef, type ElementType } from "react";

/**
 * A container that adds resposive gutters and a max width to its children.
 */
export const Gutters = forwardRef(function VanillaGutters(
    props: {
        maxWidth?: number;
        children: React.ReactNode;
        tag?: ElementType;
        className?: string;
    },
    ref: React.RefObject<HTMLElement>,
) {
    return (
        <Container tag={props.tag} className={props.className} ref={ref} fullGutter={true} maxWidth={props.maxWidth}>
            {props.children}
        </Container>
    );
});
