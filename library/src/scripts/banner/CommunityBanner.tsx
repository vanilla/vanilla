/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Banner from "@library/banner/Banner";
import { MemoryRouter } from "react-router";

interface IProps {
    title?: string; // Often the message to display isn't the real H1
    description?: string;
    className?: string;
    backgroundImage?: string;
    contentImage?: string;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export default function CommunityBanner(props: IProps) {
    return (
        <MemoryRouter>
            <Banner {...props} />
        </MemoryRouter>
    );
}
