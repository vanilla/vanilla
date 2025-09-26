import SiteTotals from "@vanilla/injectables/SiteTotalsFragment";
import Utils from "@vanilla/injectables/Utils";
import Components from "@vanilla/injectables/Components";
import React from "react";

export default function SiteTotalsFragment(props: SiteTotals.Props) {
    const { totals, containerOptions, formatNumbers } = props;
    const { background, alignment, textColor } = containerOptions ?? {};

    const formatFn = formatNumbers ? Utils.formatNumberCompact : Utils.formatNumber;

    return (
        <Components.LayoutWidget
            interWidgetSpacing="none" // Ensures no extra spacing between this widget and other widgets.
            className={"siteTotalsFragment__root"}
            style={{
                // These values are optional, so if they aren't set, they won't do anything.
                color: textColor,
                ...Utils.Css.background(background),

                // Use the widget alignment to determine the alignment of the content inside the widget.
                alignItems: alignment,
            }}
        >
            <Components.Gutters>
                <div className={"siteTotalsFragment__content"}>
                    {totals.map((total) => {
                        return (
                            <div
                                key={total.recordType}
                                className="siteTotalsFragment__countContainer"
                                aria-label={`${Utils.translate(total.label)}: ${Utils.formatNumber(total.count)}`}
                            >
                                <Components.Icon icon={total.iconName} className={"siteTotalsFragment__icon"} />

                                <span title={Utils.formatNumber(total.count)} className={"siteTotalsFragment__count"}>
                                    {total.isCalculating ? "???" : formatFn(total.count)}
                                </span>

                                <span className={"siteTotalsFragment__label"}>{Utils.translate(total.label)}</span>
                            </div>
                        );
                    })}
                </div>
            </Components.Gutters>
        </Components.LayoutWidget>
    );
}
