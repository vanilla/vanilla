/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import throttle from "lodash/throttle";
import React, { useContext, useEffect, useState } from "react";
import { ISectionClasses } from "@library/layout/Section.styles";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
import { mediaQueryFactory } from "@library/layout/types/mediaQueryFactory";
import { IAllSectionDevices, ISectionMediaQueryFunction } from "@library/layout/types/interface.panelLayout";
import { twoColumnClasses, twoColumnVariables } from "@library/layout/types/layout.twoColumns";
import { threeColumnClasses, threeColumnVariables } from "@library/layout/types/layout.threeColumns";

export interface ISectionProps {
    type: SectionTypes;
    currentDevice: string;
    Devices: IAllSectionDevices;
    isCompact: boolean; // Usually mobile and/or xs, but named this way to be more generic and not be confused with the actual mobile media query
    isFullWidth: boolean; // Usually desktop and no bleed, but named this way to be more generic and just to mean it's the full size
    classes: ISectionClasses;
    currentSectionVariables: any;
    mediaQueries: ISectionMediaQueryFunction;
    contentWidth: number;
    calculateDevice: () => any;
    sectionSpecificStyles: (style) => any | undefined;
}

const sectionDataByType = (type: SectionTypes): ISectionProps => {
    const section = {
        variables: type === SectionTypes.TWO_COLUMNS ? twoColumnVariables() : threeColumnVariables(),
        classes: type === SectionTypes.TWO_COLUMNS ? twoColumnClasses() : threeColumnClasses(),
    };

    const currentDevice = section.variables.calculateDevice().toString();

    return {
        type,
        currentDevice,
        Devices: section.variables.Devices as any,
        isCompact: section.variables.isCompact(currentDevice),
        isFullWidth: section.variables.isFullWidth(currentDevice),
        classes: section.classes as ISectionClasses,
        currentSectionVariables: section.variables,
        mediaQueries: mediaQueryFactory(section.variables.mediaQueries, type),
        contentWidth: section.variables.contentWidth,
        calculateDevice: section.variables.calculateDevice,
        sectionSpecificStyles: section.variables["sectionSpecificStyles"] ?? undefined,
    };
};

const SectionContext = React.createContext<ISectionProps>(sectionDataByType(SectionTypes.THREE_COLUMNS));

export default SectionContext;

export function useSection() {
    return useContext(SectionContext);
}

export function SectionProvider(props: { type?: SectionTypes; children: React.ReactNode }) {
    const { type = SectionTypes.THREE_COLUMNS, children } = props;

    const [deviceInfo, setDeviceInfo] = useState<ISectionProps>(sectionDataByType(type));

    useEffect(() => {
        const throttledUpdate = throttle(() => {
            setDeviceInfo(sectionDataByType(type));
        }, 100);
        window.addEventListener("resize", throttledUpdate);
        return () => {
            window.removeEventListener("resize", throttledUpdate);
        };
    }, [type, setDeviceInfo]);

    return <SectionContext.Provider value={deviceInfo}>{children}</SectionContext.Provider>;
}

/**
 * HOC to inject DeviceContext as props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withSection<T extends ISectionProps = ISectionProps>(WrappedComponent: React.ComponentType<T>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    const ComponentWithDevice = (props: Optionalize<T, ISectionProps>) => {
        return (
            <SectionContext.Consumer>
                {(context) => {
                    // https://github.com/Microsoft/TypeScript/issues/28938
                    return <WrappedComponent {...(context as T)} {...(props as T)} />;
                }}
            </SectionContext.Consumer>
        );
    };
    ComponentWithDevice.displayName = `withSection(${displayName})`;
    return ComponentWithDevice;
}
