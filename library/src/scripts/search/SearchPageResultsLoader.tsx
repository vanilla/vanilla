/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LoadingRectangle, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { t } from "@vanilla/i18n/src";

interface IProps {
    count: number;
}

export function SearchPageResultsLoader(props: IProps) {
    const { count } = props;
    return (
        <div>
            {Array.from(new Array(count)).map((_, i) => {
                return (
                    <div key={i}>
                        <ScreenReaderContent>{t("Loading")}</ScreenReaderContent>
                        <LoadingRectangle height={1} width={"100%"} />
                        <LoadingSpacer height={10} width={"100%"} />
                        <div style={{ display: "flex", flexDirection: "row", paddingLeft: 8 }}>
                            <LoadingRectangle height={25} width={25} style={{ marginRight: 10, borderRadius: "50%" }} />
                            <div>
                                <LoadingRectangle height={15} width={150} />
                                <LoadingSpacer height={5} width={"100%"} />
                                <div
                                    style={{
                                        display: "flex",
                                        flexDirection: "row",
                                    }}
                                >
                                    <LoadingRectangle height={15} width={150} style={{ marginRight: 10 }} />
                                    <LoadingRectangle height={15} width={80} style={{ marginRight: 10 }} />
                                    <LoadingRectangle height={15} width={120} style={{ marginRight: 10 }} />
                                </div>
                            </div>
                        </div>
                        <LoadingSpacer height={15} width={400} />
                    </div>
                );
            })}
        </div>
    );
}
