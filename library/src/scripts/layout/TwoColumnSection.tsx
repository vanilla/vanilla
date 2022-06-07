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
    secondaryTop?: React.ReactNode; // mapped to rightTop
    secondaryBottom?: React.ReactNode; // mapped to rightBottom
    isInverted?: boolean;
}

function TwoColumnSection(props: IProps) {
    const leftTopContent = props.isInverted ? props.secondaryTop : undefined;
    const leftBottomContent = props.isInverted ? props.secondaryBottom : undefined;
    const rightTopContent = props.isInverted ? undefined : props.secondaryTop;
    const rightBottomContent = props.isInverted ? undefined : props.secondaryBottom;

    return (
        <SectionProvider type={SectionTypes.TWO_COLUMNS}>
            <Section
                {...props}
                middleTop={props.mainTop}
                middleBottom={props.mainBottom}
                leftTop={leftTopContent}
                leftBottom={leftBottomContent}
                rightTop={rightTopContent}
                rightBottom={rightBottomContent}
                displayLeftColumn={props.isInverted}
                displayRightColumn={!props.isInverted}
            />
        </SectionProvider>
    );
}

export default TwoColumnSection;
