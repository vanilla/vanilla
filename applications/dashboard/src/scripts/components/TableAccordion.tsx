/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { DropDownArrow } from "@vanilla/ui/src/forms/shared/DropDownArrow";
import { useMemo, useRef, useState } from "react";

interface IProps {
    toggleButtonContent: React.ReactNode;
    children: React.ReactNode;
    className?: string;
    contentClassName?: string;
    isExpanded?: boolean;
    onExpandChange?: (isExpanded: boolean) => void;
    lazy?: boolean;
}

export function TableAccordion(props: IProps) {
    const { toggleButtonContent, onExpandChange, lazy } = props;
    const [isExpanded, setIsExpanded] = useState(props.isExpanded);

    const ref = useRef<HTMLDivElement>(null);

    const title = isExpanded ? t("Collapse") : t("Expand");

    const toggleID = useMemo(() => uniqueIDFromPrefix("accordion_toggle"), []);
    const contentID = useMemo(() => uniqueIDFromPrefix("accordion_content"), []);

    return (
        <div className={props.className}>
            <div>
                <Button
                    id={toggleID}
                    title={title}
                    className={classes.accordionToggleButton}
                    buttonType={ButtonTypes.CUSTOM}
                    onClick={() => {
                        setIsExpanded(!isExpanded);
                        onExpandChange?.(!isExpanded);
                    }}
                    controls={contentID}
                >
                    <div>
                        <DropDownArrow className={classes.accordionArrow(isExpanded)} />
                    </div>
                    {toggleButtonContent}
                </Button>
            </div>
            {(!lazy || isExpanded) && (
                <div
                    id={contentID}
                    className={cx(classes.accordionContentContainer, { isExpanded }, props.contentClassName)}
                    aria-expanded={isExpanded}
                >
                    <div ref={ref}>{props.children}</div>
                </div>
            )}
        </div>
    );
}

const classes = {
    accordionToggleButton: css({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        textAlign: "start",
        minHeight: 40,
        width: "100%",
        minWidth: 200,
    }),
    accordionArrow: (isExpanded?: boolean) => {
        return css({
            transform: isExpanded ? undefined : "rotate(-90deg)",
            width: 10,
            height: 10,
            marginRight: 8,
        });
    },
    accordionContentContainer: css({
        position: "relative",
        display: "grid",
        gridTemplateRows: "0fr",
        transition: "grid-template-rows 0.3s",
        overflow: "hidden",
        paddingLeft: 18, // account for the accordion size and gap.

        "&.isExpanded": {
            gridTemplateRows: "1fr",
        },
        "& > *": {
            // Needed so the grid collapsed to 0fr
            minHeight: 0,
        },
    }),
};
