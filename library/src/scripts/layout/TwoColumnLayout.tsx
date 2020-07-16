/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import PanelLayout, { IPanelLayoutProps } from "@library/layout/PanelLayout";
import { withLayout, LayoutProvider } from "@library/layout/LayoutContext";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";

interface IProps
    extends Omit<
        IPanelLayoutProps,
        "leftTop" | "leftBottom" | "renderLeftPanelBackground" | "ILayoutProps" | "middleTop" | "middleBottom"
    > {
    mainTop?: React.ReactNode; // mapped to middleTop
    mainBottom?: React.ReactNode; // mapped to middleBottom
}

function TwoColumnLayout(props: IProps) {
    return (
        <LayoutProvider type={LayoutTypes.TWO_COLUMNS}>
            <PanelLayout {...props} middleTop={props.mainTop} middleBottom={props.mainBottom} />
        </LayoutProvider>
    );
}

export default withLayout(TwoColumnLayout);
