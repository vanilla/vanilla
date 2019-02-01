/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent, px } from "csx";

export const mediaQueries = () => {

    const panelLayoutBreakPoints = {
        noBleed      : $global-content_width,
        twoColumn    : $global-twoColumn_breakpoint,
        oneColumn    : $panelLayout-twoColumn_breakpoint - $global-panel_paddedWidth,
        }
};

    const panelLayout = {
        noBleed: media({maxWidth: })
    };

    return {panelLayout};
};


@mixin mediaQuery-panelLayout_noBleed {
    @media (max-width: #{$panelLayout-noBleed_breakpoint}) {
        @content;
        }
    }

@mixin mediaQuery-panelLayout_twoColumn {
    @media (max-width: #{$panelLayout-twoColumn_breakpoint}) {
        @content;
        }
    }

@mixin mediaQuery-panelLayout_oneColumn {
    @media (max-width: #{$panelLayout-oneColumn_breakpoint}) {
        @content;
        }
    }

@mixin mediaQuery-panelLayout_xs {
    @media (max-width: #{$global-xs_breakpoint}) {
        @content;
        }
    }

};
