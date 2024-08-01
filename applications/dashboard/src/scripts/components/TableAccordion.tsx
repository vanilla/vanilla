/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { useMeasure } from "@vanilla/react-utils";
import { DropDownArrow } from "@vanilla/ui/src/forms/shared/DropDownArrow";
import { useState, useRef, useMemo } from "react";
import { useSpring, animated } from "react-spring";

interface IProps {
    toggleButtonContent: React.ReactNode;
    children: React.ReactNode;
    className?: string;
    isExpanded?: boolean;
    onExpandChange?: (isExpanded: boolean) => void;
}

export function TableAccordion(props: IProps) {
    const { toggleButtonContent, onExpandChange } = props;
    const [isExpanded, setIsExpanded] = useState(props.isExpanded ?? false);

    const ref = useRef<HTMLDivElement>(null);
    const measurements = useMeasure(ref);

    const { height: animatedHeight } = useSpring({
        height: isExpanded ? measurements.height + 8 : 0,
    });

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
            <animated.div
                id={contentID}
                style={{
                    height: props.isExpanded ? (isExpanded ? measurements.height + 8 : 0) : animatedHeight,
                }}
                className={classes.accordionContentContainer}
                aria-expanded={isExpanded}
            >
                <div ref={ref}>{props.children}</div>
            </animated.div>
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
        display: "block",
        overflow: "hidden",
        paddingLeft: 18, // account for the accordion size and gap.
    }),
};
