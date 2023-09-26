/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { Widget } from "@library/layout/Widget";
import { getMeta } from "@library/utility/appUtils";
import { CategoryFollowDropDown } from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import React from "react";

interface IProps extends React.ComponentProps<typeof CategoryFollowDropDown> {}

export function CategoryFollowWidgetPreview(props: IProps) {
    const { borderRadius, buttonColor, textColor, alignment = "end" } = props;
    const emailEnabled = getMeta("emails.digest", false);
    return (
        <Widget>
            <CategoryFollowDropDown
                // Required
                userID={-10}
                categoryID={-10}
                categoryName={"General"}
                emailDigestEnabled={emailEnabled}
                emailEnabled={emailEnabled}
                // For preview
                preview
                // Style overrides
                borderRadius={borderRadius}
                buttonColor={buttonColor}
                textColor={textColor}
                alignment={alignment}
            />
        </Widget>
    );
}
