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
    const { contents } = props;
    return (
        <MemoryRouter>
            <TitleBar useMobileBackButton={false} hamburger={contents} />
        </MemoryRouter>
    );
}
