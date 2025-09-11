/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { ISiteTotalCount, ISiteTotalsContainer } from "@library/siteTotals/SiteTotals.variables";

namespace SiteTotalsFragmentInjectable {
    export interface Props {
        totals: ISiteTotalCount[];
        containerOptions?: {
            background?: {
                color?: string;
                image?: string;
            };
            alignment?: ISiteTotalsContainer["alignment"];
            textColor?: string;
        };
        formatNumbers?: boolean;
    }
}

const SiteTotalsFragmentInjectable = {};

export default SiteTotalsFragmentInjectable;
