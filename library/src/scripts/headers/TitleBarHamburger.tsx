/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import TitleBar from "@library/headers/TitleBar";
import * as React from "react";
import { MemoryRouter } from "react-router";

interface IProps {
    contents: string; // HTML content
}

export function TitleBarHamburger(props: IProps) {
    return (
        <MemoryRouter>
            <TitleBar useMobileBackButton={false} />
        </MemoryRouter>
    );
}
