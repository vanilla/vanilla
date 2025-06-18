/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import SearchFragment from "@library/widget-fragments/SearchFragment.template";
import "./SearchFragment.template.css";

export default {
    title: "Fragments/Search",
};

export function Template() {
    return (
        <SearchFragment
            title={"Search Title"}
            description={
                "Sample Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
            }
        />
    );
}
