/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { SectionProvider } from "@library/layout/LayoutContext";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
import Section, { ISectionProps } from "@library/layout/Section";

interface IProps extends ISectionProps {}

function SectionThreeColumns(props: IProps) {
    return (
        <SectionProvider type={SectionTypes.THREE_COLUMNS}>
            <Section {...props} />
        </SectionProvider>
    );
}

export default SectionThreeColumns;
