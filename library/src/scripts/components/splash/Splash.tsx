/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import splashStyles from "@library/components/splash/splashStyles";
import Heading from "@library/components/Heading";
import { color, ColorHelper } from "csx";
import { BackgroundImageProperty } from "csstype";
import ConditionalWrap from "@library/components/ConditionalWrap";
import { style } from "typestyle";

interface ISplashStyles {
    colors?: {
        fg?: ColorHelper;
        bg?: ColorHelper;
        primary?: ColorHelper;
    };
    backgroundImage?: BackgroundImageProperty;
    fullWidth?: boolean;
    transparentButton?: boolean;
}

interface IProps {
    title: string; // Often the message to display isn't the real H1
    className?: string;
    styles: ISplashStyles;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export default class Splash extends React.Component<IProps> {
    public static defaultProps = {
        styles: {
            colors: {},
            fullWidth: true,
            transparentButton: true,
        },
    };
    public render() {
        const classes = splashStyles();
        const { title, className, styles } = this.props;
        const backgroundStyles = style({
            backgroundColor: "orange",
        });
        return (
            <div className={classNames("splash", className, classes.root)}>
                {title && <Heading title={title} />}
                <ConditionalWrap condition={styles.fullWidth!} className="container">
                    <div>t</div>
                </ConditionalWrap>
            </div>
        );
    }
}
