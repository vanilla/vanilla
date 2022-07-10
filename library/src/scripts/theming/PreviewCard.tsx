import React, { ReactNode, useState } from "react";

import previewCardClasses from "@library/theming/PreviewCard.styles";
import { useFocusWatcher } from "@vanilla/react-utils";
import LinkAsButton from "@library/routing/LinkAsButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import Button from "@library/forms/Button";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { css, cx } from "@emotion/css";
import { Flag } from "@library/theming/CurrentThemeInfo";
export interface IPreviewCardProps {
    previewImage: ReactNode;
    name?: string;
    background?: string;
    dropdownContent?: ReactNode;
    actionButtons?: ReactNode;
    active?: boolean;
}

const PreviewCard = React.forwardRef(function PreviewCard(
    props: IPreviewCardProps,
    ref: React.RefObject<HTMLDivElement>,
) {
    const [hasFocus, setHasFocus] = useState(false);
    useFocusWatcher(ref, setHasFocus);

    const classes = previewCardClasses();

    const { dropdownContent, actionButtons } = props;
    const renderOverlay = !!dropdownContent || !!actionButtons;

    return (
        <div
            className={cx(
                classes.constraintContainer,
                props.active && classes.constraintContainerActive,
                css({
                    backgroundColor: props.background,
                }),
            )}
        >
            <div className={classes.ratioContainer}>
                <div
                    ref={ref}
                    className={cx(hasFocus && classes.isFocused, classes.container)}
                    tabIndex={0}
                    title={props.name}
                >
                    <div className={classes.previewContainer}>
                        <div className={classes.menuBar}>
                            {[0, 1, 2].map((key) => (
                                <span key={key} className={classes.menuBarDots}></span>
                            ))}
                        </div>
                        {props.previewImage}
                    </div>
                    {!!props.active && (
                        <div className={classes.activeOverlay}>
                            <Flag className={classes.flagSizeAndPosition} />
                        </div>
                    )}
                    {renderOverlay && (
                        <div className={cx(classes.overlay)}>
                            <div className={classes.overlayBg}></div>
                            {!!dropdownContent && <div className={classes.actionDropdown}>{dropdownContent}</div>}
                            {!!actionButtons && <div className={classes.actionButtons}>{actionButtons}</div>}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
});

export type ClickHandlerOrUrl = string | VoidFunction;

export function LinkOrButton(props: { onClick: ClickHandlerOrUrl; children: React.ReactNode; isDropdown?: boolean }) {
    const classes = previewCardClasses();
    if (typeof props.onClick === "string") {
        if (props.isDropdown) {
            return <DropDownItemLink to={props.onClick}>{props.children}</DropDownItemLink>;
        } else {
            return (
                <LinkAsButton className={classes.actionButton} to={props.onClick}>
                    {props.children}
                </LinkAsButton>
            );
        }
    } else {
        if (props.isDropdown) {
            return <DropDownItemButton onClick={props.onClick}>{props.children}</DropDownItemButton>;
        } else {
            return (
                <Button className={classes.actionButton} onClick={props.onClick}>
                    {props.children}
                </Button>
            );
        }
    }
}

export default PreviewCard;
