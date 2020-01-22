/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { currentThemeClasses } from "./currentThemeStyles";
import DateTime from "@library/content/DateTime";

interface IThemeInfo {
    [key: string]: {
        type: string,
        info: string
    };
}

interface IProps {
    name: string;
    info: IThemeInfo;
    support?: string;
    onEdit?: () => void;
    onRename?: () => void;
    onCopy?: () => void;
}
interface IState {}

export default class CurrentThemeInfo extends React.Component<IProps, IState> {
    constructor(props) {
        super(props);
    }

    public render() {
        const classes = currentThemeClasses();
        const { name, info, support } = this.props;
        return (
            <React.Fragment>
                <section className={classes.themeContainer}>
                    <div className={classes.themeInfo}>
                        <div className={classes.flag}>Current Theme</div>
                        <div className={classes.name}>
                            <h3>{name}</h3>
                        </div>
                        {Object.entries(info).map(([key, value], i) => (
                            <div className={classes.description}>
                                <p><strong>{key}</strong>: { value.type === 'date' ? <DateTime timestamp={value.info} /> : value.info}</p>
                            </div>
                        ))
                        }
                    </div>
                    <div className={classes.themeActionButtons}>
                        <Button onClick={this.props.onEdit}>{t("Edit")}</Button>
                        <Button onClick={this.props.onRename}>{t("Rename")}</Button>
                        <Button onClick={this.props.onCopy}>{t("Copy")}</Button>
                    </div>
                </section>
            </React.Fragment>
        );
    }
}
