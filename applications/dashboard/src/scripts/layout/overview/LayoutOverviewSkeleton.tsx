/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { FauxWidget, fetchOverviewComponent } from "@dashboard/layout/overview/LayoutOverview";
import { LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import { ContainerContextReset } from "@library/layout/components/Container";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { SectionBehaviourContext } from "@library/layout/SectionBehaviourContext";
import SectionThreeColumns from "@library/layout/ThreeColumnSection";
import React, { useMemo } from "react";
import random from "lodash/random";
import { SectionOneColumn } from "@library/layout/SectionOneColumn";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { SectionFullWidth } from "@library/layout/SectionFullWidth";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import PanelWidget from "@library/layout/components/PanelWidget";
import { Widget } from "@library/layout/Widget";
import { WidgetLayout } from "@library/layout/WidgetLayout";

export function LayoutOverviewSkeleton() {
    return (
        <DeviceProvider>
            <ContainerContextReset>
                <SectionBehaviourContext.Provider value={{ isSticky: false, autoWrap: false, useMinHeight: false }}>
                    <WidgetLayout>
                        <div>
                            <SectionFullWidth>
                                <LoadingRectangle height={200} />
                            </SectionFullWidth>
                            <RandomSections />
                        </div>
                    </WidgetLayout>
                </SectionBehaviourContext.Provider>
            </ContainerContextReset>
        </DeviceProvider>
    );
}

function RandomSections() {
    const count = useMemo(() => {
        return random(1, 3, false);
    }, []);

    const iter = Array.from(new Array(count));

    return (
        <>
            {iter.map((_, i) => {
                return <RandomSection key={i} />;
            })}
        </>
    );
}

function RandomSection() {
    const rand = useMemo(() => {
        return random(1, 3, false);
    }, []);

    switch (rand) {
        case 1:
            return (
                <SectionOneColumn>
                    <RandomWidget />
                </SectionOneColumn>
            );
        case 2:
            return (
                <SectionTwoColumns mainBottom={<RandomWidgets inPanel />} secondaryBottom={<RandomWidgets inPanel />} />
            );
        case 3:
            return (
                <SectionThreeColumns
                    leftBottom={<RandomWidgets inPanel />}
                    middleBottom={<RandomWidgets heights={[220, 300]} inPanel />}
                    rightBottom={<RandomWidgets inPanel />}
                />
            );
        default:
            return <></>;
    }
}

function RandomWidgets(props: { inPanel?: boolean; heights?: number[] }) {
    const count = useMemo(() => {
        return random(1, 3, false);
    }, []);

    const iter = Array.from(new Array(count));

    return (
        <>
            {iter.map((_, i) => {
                let result = <RandomWidget key={i} />;
                if (props.inPanel) {
                    result = <PanelWidget key={i}>{result}</PanelWidget>;
                }
                return result;
            })}
        </>
    );
}

function RandomWidget(props: { heights?: number[] }) {
    const heights = props.heights ?? [60, 100, 140, 180, 220];
    const heightIndex = useMemo(() => {
        return random(0, heights.length - 1, false);
    }, []);
    const height = heights[heightIndex];
    return (
        <Widget>
            <LoadingRectangle height={height} width={"100%"} />
        </Widget>
    );
}
