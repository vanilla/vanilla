/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render } from "@testing-library/react";
import LinkAsButton from "@library/routing/LinkAsButton";

describe("LinkAsButton", () => {
    it(`Anchors href is "to" prop value`, async () => {
        const { findByText } = render(<LinkAsButton to={"http://vanillaforums.com"}>Test</LinkAsButton>);
        expect(await findByText(/Test/)).toHaveAttribute("href", "http://vanillaforums.com");
        expect(await findByText(/Test/)).toHaveAttribute("title", "Test");
    });
    it(`Title is same as child string`, async () => {
        const { findByText } = render(<LinkAsButton to={"http://vanillaforums.com"}>Test</LinkAsButton>);
        expect(await findByText(/Test/)).toHaveAttribute("title", "Test");
    });
    it(`Title fallbacks to undefined when child is not a string`, async () => {
        const { getByRole } = render(
            <LinkAsButton to={"http://vanillaforums.com"}>
                <span>Some text node</span>
            </LinkAsButton>,
        );
        expect(await getByRole(/button/)).not.toHaveAttribute("title", "Test");
    });
    it(`Render button when enabled`, async () => {
        const { getByRole } = render(<LinkAsButton to={"http://vanillaforums.com"}>Test</LinkAsButton>);
        expect(await getByRole(/button/)).not.toBe(null);
    });
    it(`Render span when disabled`, async () => {
        const { getByRole } = render(
            <LinkAsButton to={"http://vanillaforums.com"} disabled>
                Test
            </LinkAsButton>,
        );
        expect(await getByRole(/presentation/)).not.toBe(null);
    });
});
