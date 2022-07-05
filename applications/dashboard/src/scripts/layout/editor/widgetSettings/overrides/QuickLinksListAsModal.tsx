/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { NavigationLinksModal } from "@dashboard/components/navigation/NavigationLinksModal";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import { IControlProps } from "@vanilla/json-schema-forms";
import React, { useState } from "react";

/**
 * This constant contains the condition when a custom component should be displayed
 * and the custom component itself in the callback field
 */
export const QUICK_LINKS_LIST_AS_MODAL = {
    condition: (props: IControlProps): boolean => {
        return props.control.inputType === "modal" && props.rootSchema.description === "Quick Links";
    },
    callback: function DashboardModalControl(props: IControlProps) {
        const { control, instance } = props;
        const [isOpen, setOpen] = useState(false);
        function openModal() {
            setOpen(true);
        }
        function closeModal() {
            setOpen(false);
        }
        return (
            <>
                <div className="input-wrap">
                    <Button onClick={openModal} buttonType={ButtonTypes.STANDARD}>
                        {control["modalTriggerLabel"]}
                    </Button>
                </div>
                <NavigationLinksModal
                    title={"Quick Links"}
                    isNestingEnabled={false}
                    navigationItems={(instance ?? quickLinksVariables().links ?? []) as any}
                    isVisible={isOpen}
                    onCancel={closeModal}
                    onSave={(newData) => {
                        props.onChange(newData);
                        closeModal();
                    }}
                />
            </>
        );
    },
};
