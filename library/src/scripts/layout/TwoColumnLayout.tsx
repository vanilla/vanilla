/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import PanelLayout, { IPanelLayoutProps } from "@library/layout/PanelLayout";

interface IProps extends Omit<IPanelLayoutProps, "leftTop" | "leftBottom" | "renderLeftPanelBackground"> {}

export default function TwoColumnLayout(props: IProps) {
    return <PanelLayout {...props} isTwoColumnLayout={true} />;
}
