/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    DynamicComponentTypes,
    IHamburgerDynamicComponent,
    PartialBy,
    useHamburgerMenuContext,
} from "@library/contexts/HamburgerMenuContext";
import Hamburger from "@library/flyouts/Hamburger";
import React, { useEffect } from "react";

interface IProps {
    componentsAddedToContext?: Array<PartialBy<IHamburgerDynamicComponent, "id">>;
    componentsToRemoveFromContext?: Array<IHamburgerDynamicComponent["id"]>;
}

export function HamburgerWithComponents(props: IProps) {
    const { componentsAddedToContext, componentsToRemoveFromContext } = props;
    const { addComponent, removeComponentByID } = useHamburgerMenuContext();

    useEffect(() => {
        if (componentsAddedToContext) {
            componentsAddedToContext?.forEach((componentConfig) => {
                addComponent(componentConfig);
            });
        }
    }, [componentsAddedToContext]);

    useEffect(() => {
        if (componentsAddedToContext) {
            componentsToRemoveFromContext?.forEach((id) => {
                removeComponentByID(id);
            });
        }
    }, [componentsToRemoveFromContext]);

    return componentsAddedToContext ? (
        <Hamburger forceHamburgerOpen />
    ) : (
        <>No components added, use componentsAddedToContext prop</>
    );
}
