import * as React from "react";
import { visibility } from "@library/styles/styleHelpersVisibility";
import classNames from "classnames";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";

/**
 *
 */
export default function ButtonSwitch(props) {
    const visibilityClasses = visibility();
    const isLoading = props.isLoading;

    const content = isLoading ? (
        <ButtonLoader />
    ) : (
        <>
            <span className={classNames({ [visibilityClasses.visuallyHidden]: !props.status })}>on</span>
            <span className={classNames({ [visibilityClasses.visuallyHidden]: props.status })}>off</span>
        </>
    );

    return (
        <Button baseClass={ButtonTypes.RESET} onClick={props.onClick} role={"switch"} aria-checked={props.status}>
            {props.label}
            {content}
        </Button>
    );
}
