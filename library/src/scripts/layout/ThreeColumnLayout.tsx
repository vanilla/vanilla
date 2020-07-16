/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { LayoutProvider, withLayout } from "@library/layout/LayoutContext";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import PanelLayout, { IPanelLayoutProps } from "@library/layout/PanelLayout";

interface IProps extends Omit<IPanelLayoutProps, "ILayoutProps"> {}

function ThreeColumnLayout(props: IProps) {
    return (
        <LayoutProvider type={LayoutTypes.THREE_COLUMNS}>
            <PanelLayout {...props} />
        </LayoutProvider>
    );
}

export default withLayout(ThreeColumnLayout);
