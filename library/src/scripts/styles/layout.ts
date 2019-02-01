/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent, px } from "csx";

export const layout = () => {
    const gutterSize = 24;

    const gutter = {
        size: px(gutterSize),
        halfSize: px(gutterSize/2),
        quarterSize: px(gutterSize/4),
    };

    const panelWidth = 216;
    const panel = {
        width: px(216),
        paddedWidth: panelWidth + gutterSize * 2,
    };



    const middleColumn = {
        width: "",
        paddedWidth: "",
    };



    const content = {};
    const globalBreakPoints = {
        twoColumn: px(1200),
        xs: px(500),
    };

    const evenColumns = {
        breakPoint:
    };

    return {};
};

/*
                                $global-gutter_size                                  : 24px;
                                $global-gutter_halfSize                              : $global-gutter_size / 2;
                                $global-gutter_quarterSize                           : $global-gutter_size / 4;

                                $global-panel_width                                  : 216px !default;
                                $global-panel_paddedWidth                            : $global-panel_width + $global-gutter_size * 2 !default;



$global-middleColumn_width                           : 672px;
$global-middleColumn_paddedWidth                     : $global-middleColumn_width + $global-gutter_size;

$global-content_width                                : $global-panel_paddedWidth * 2 + $global-middleColumn_paddedWidth + $global-gutter_size * 3; // *3 from margin between columns and half margin on .container

$global-twoColumn_breakpoint                         : 1200px !default; // Generic breakpoint for 2 columns
$global-xs_breakpoint                                : 500px !default;

// Hard coded columns
$evenColumns_breakpoint                              : $global-twoColumn_breakpoint !default;
$evenColumns-threeColumns-breakToOne                 : $evenColumns_breakpoint !default;

// Uses CSS Columns
$flexColumns-twoColumns_breakToOne                   : $global-twoColumn_breakpoint !default;
$flexColumns-threeColumns_breakToTwo                 : $global-twoColumn_breakpoint !default;
$flexColumns-threeColumns_breakToOne                 : 500px !default;
*/
