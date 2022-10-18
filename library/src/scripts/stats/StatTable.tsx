/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { t } from "@vanilla/i18n";
import { Stat } from "./Stat";
import { statClasses } from "./Stat.styles";
import { cx } from "@emotion/css";
import { PageBox } from "@library/layout/PageBox";
import { BorderType } from "@library/styles/styleHelpersBorders";
import Heading from "@library/layout/Heading";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";

export interface IUserAnalytics {
    points?: number;
    posts?: number;
    visits?: number;
    joinDate?: ReactNode;
    lastActive?: ReactNode;
}

export interface IUserAnalyticsProps {
    title: string;
    userInfo: IUserAnalytics;
    afterLink?: ReactNode;
}

export default function StatTable(props: IUserAnalyticsProps) {
    const { title, userInfo, afterLink } = props;
    const classes = statClasses();

    if (!userInfo) {
        return <></>;
    }

    return (
        <>
            <Heading title={title ?? t("Analytics")} className={classes.title} />

            <PageBox options={{ borderType: BorderType.BORDER }} className={classes.container}>
                {Object.keys(userInfo).length ? (
                    Object.keys(userInfo).map((key, index) => {
                        return <Stat key={index} value={userInfo[key]} label={key} classNames={classes.hasBorder} />;
                    })
                ) : (
                    <>
                        <div className={cx(classes.stat, classes.hasBorder)}>
                            <LoadingRectangle inline height={73} />
                        </div>
                        <div className={cx(classes.stat, classes.hasBorder)}>
                            <LoadingRectangle inline height={73} />
                        </div>
                        <div className={cx(classes.stat, classes.hasBorder)}>
                            <LoadingRectangle inline height={73} />
                        </div>
                        <div className={cx(classes.stat, classes.hasBorder)}>
                            <LoadingRectangle inline height={73} />
                        </div>
                    </>
                )}
            </PageBox>

            {afterLink ?? afterLink}
        </>
    );
}
