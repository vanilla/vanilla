/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";

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
                        <LoadingRectange height={1} width={"100%"} />
                        <LoadingSpacer height={10} width={"100%"} />
                        <div style={{ display: "flex", flexDirection: "row" }}>
                            <LoadingRectange height={25} width={25} style={{ marginRight: 10, borderRadius: "50%" }} />
                            <div>
                                <LoadingRectange height={20} width={150} />
                                <LoadingSpacer height={5} width={"100%"} />
                                <div
                                    style={{
                                        display: "flex",
                                        flexDirection: "row",
                                    }}
                                >
                                    <LoadingRectange height={20} width={150} style={{ marginRight: 10 }} />
                                    <LoadingRectange height={20} width={80} style={{ marginRight: 10 }} />
                                    <LoadingRectange height={20} width={120} style={{ marginRight: 10 }} />
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
