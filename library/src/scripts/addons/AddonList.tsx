/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";

import { List } from "@library/lists/List";
import { PageBox } from "@library/layout/PageBox";
import { BorderType } from "@library/styles/styleHelpersBorders";

interface IProps {
    children: React.ReactNode;
}

function AddonList({ children }: IProps) {
    return (
        <List>
            {React.Children.map(children, (child, i) => (
                <PageBox key={i} as="li" options={{ borderType: BorderType.SEPARATOR }}>
                    {child}
                </PageBox>
            ))}
        </List>
    );
}

export default AddonList;
