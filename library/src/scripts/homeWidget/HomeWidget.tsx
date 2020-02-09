/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IHomeWidgetItemOptions } from "@library/homeWidget/HomeWidgetItem.styles";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";

export interface IHomeWidgetProps {
    containerOptions: IHomeWidgetContainerOptions;
    itemOptions: IHomeWidgetItemOptions;
    maxItemCount?: number;
}

interface IProps {}
