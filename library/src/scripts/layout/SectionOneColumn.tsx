/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Container } from "@library/layout/components/Container";
import React from "react";

interface IProps {
    contents: React.ReactNode;
    isNarrow: boolean;
}

export function SectionOneColumn(props: IProps) {
    return (
        <Container fullGutter narrow={props.isNarrow}>
            {props.contents}
        </Container>
    );
}
