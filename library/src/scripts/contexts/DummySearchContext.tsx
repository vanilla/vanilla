/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { ISearchOptionProvider, IWithSearchProps } from "./SearchContext";

export class MockSearchData implements ISearchOptionProvider {
    autocomplete() {
        return Promise.resolve([
            {
                value: "AAAAAAAAAAAA",
                label: "AAAAAAAAAAAA",
            },
            {
                value: "BBBBBBBBBBBB",
                label: "BBBBBBBBBBBB",
            },
            {
                value: "CCCCCCCCCCC",
                label: "CCCCCCCCCCC",
            },
        ]);
    }

    makeSearchUrl() {
        return "#";
    }
}
