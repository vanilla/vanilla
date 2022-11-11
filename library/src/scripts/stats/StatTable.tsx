/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { Stat } from "./Stat";
import { statClasses } from "./Stat.styles";
import { cx } from "@emotion/css";
import { PageBox } from "@library/layout/PageBox";
import { BorderType } from "@library/styles/styleHelpersBorders";
import Heading from "@library/layout/Heading";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { RecordID } from "@vanilla/utils";

export interface IStatTableProps {
    title?: string;
    data: Record<string, RecordID | ReactNode>;
}

export default function StatTable(props: IStatTableProps) {
    const { title, data } = props;
    const classes = statClasses();

    if (!data) {
        return <></>;
    }

    return (
        <div>
            {title && <Heading title={title} className={classes.title} />}

            <PageBox options={{ borderType: BorderType.BORDER }} className={classes.container}>
                {Object.keys(data).length ? (
                    Object.keys(data).map((key, index) => {
                        return (
                            <Stat key={index} value={data[key]} label={key} classNames={classes.statItemResponsive} />
                        );
                    })
                ) : (
                    <>
                        <div className={cx(classes.statItem, classes.statItemResponsive)}>
                            <LoadingRectangle inline height={73} width={100} />
                        </div>
                        <div className={cx(classes.statItem, classes.statItemResponsive)}>
                            <LoadingRectangle inline height={73} width={100} />
                        </div>
                        <div className={cx(classes.statItem, classes.statItemResponsive)}>
                            <LoadingRectangle inline height={73} width={100} />
                        </div>
                        <div className={cx(classes.statItem, classes.statItemResponsive)}>
                            <LoadingRectangle inline height={73} width={100} />
                        </div>
                    </>
                )}
            </PageBox>
        </div>
    );
}
