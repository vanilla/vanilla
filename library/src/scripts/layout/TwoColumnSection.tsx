/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import Section, { ISectionProps } from "@library/layout/Section";
import { SectionProvider } from "@library/layout/LayoutContext";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";

interface IProps
    extends Omit<
        ISectionProps,
        "leftTop" | "leftBottom" | "renderLeftPanelBackground" | "ILayoutProps" | "middleTop" | "middleBottom"
    > {
    mainTop?: React.ReactNode; // mapped to middleTop
    mainBottom?: React.ReactNode; // mapped to middleBottom
}

function TwoColumnSection(props: IProps) {
    return (
        <SectionProvider type={SectionTypes.TWO_COLUMNS}>
            <Section {...props} middleTop={props.mainTop} middleBottom={props.mainBottom} />
        </SectionProvider>
    );
}

export default TwoColumnSection;
