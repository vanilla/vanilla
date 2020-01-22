/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { toastClasses } from "@library/features/toaster/toastStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import classNames from "classnames";
import ButtonLoader from "@library/loaders/ButtonLoader";

interface ILink {
    name: string;
    type: ButtonTypes;
    onClick?: () => void;
    isLoading?: boolean;
}

interface IProps {
    className?: string;
    links: ILink[];
    message: React.ReactNode;
}

/**
 * Renders toast message with links
 */
export default class Toast extends React.Component<IProps> {
    public render() {
        const { className, links, message } = this.props;
        const classes = toastClasses();

        return (
            <div className={classNames(classes.root())}>
                <p>{message}</p>
                <div className={classes.buttons}>
                    {links.map((link, i) => (
                        <Button
                            key={i}
                            baseClass={link.type}
                            title={link.name}
                            className={classNames(classes.button)}
                            onClick={link.onClick}
                        >
                            {link.isLoading ? <ButtonLoader buttonType={link.type} /> : link.name}
                        </Button>
                    ))}
                </div>
            </div>
        );
    }
}
