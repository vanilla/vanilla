/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IUserFragment } from "@library/@types/api/users";
import DropDownItemMetas from "@library/flyouts/items/DropDownItemMetas";
import DropDownItemMeta from "@library/flyouts/items/DropDownItemMeta";
import { metasClasses } from "@library/styles/metasStyles";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";
import ProfileLink from "@library/navigation/ProfileLink";

interface IProps {
    dateInserted: string;
    insertUser: IUserFragment;
    dateUpdated: string;
    updateUser: IUserFragment;
}

export default function InsertUpdateMetas(props: IProps) {
    const classesMetas = metasClasses();
    return (
        <DropDownItemMetas>
            <DropDownItemMeta>
                <Translate
                    source="Published <0/> by <1/>"
                    c0={<DateTime timestamp={props.dateInserted} />}
                    c1={<ProfileLink className={classesMetas.metaStyle} username={props.insertUser.name} />}
                />
            </DropDownItemMeta>
            <DropDownItemMeta>
                <Translate
                    source="Updated <0/> by <1/>"
                    c0={<DateTime timestamp={props.dateUpdated} />}
                    c1={<ProfileLink className={classesMetas.metaStyle} username={props.updateUser.name} />}
                />
            </DropDownItemMeta>
        </DropDownItemMetas>
    );
}
