/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";

export type IDashboardFormStyle = {
    compact?: boolean;
    forceVerticalLabels?: boolean;
};

export const DashboardFormStyleContext = React.createContext<IDashboardFormStyle>({
    compact: false,
    forceVerticalLabels: false,
});

export function useDashboardFormStyle() {
    return React.useContext(DashboardFormStyleContext);
}
