import TitleBar from "@library/headers/TitleBar";
import * as React from "react";
import { t } from "@library/utility/appUtils";
import SearchContext from "@library/contexts/SearchContext";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import { MemoryRouter } from "react-router";
import className from "classnames";

export function TitleBarHamburger(props) {
    const { contents } = props;
    return (
        <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
            <MemoryRouter>
                <TitleBar
                    title={"test"}
                    useMobileBackButton={false}
                    mobileDropDownContent={<div dangerouslySetInnerHTML={{ __html: contents }} />}
                />
            </MemoryRouter>
        </SearchContext.Provider>
    );
}
