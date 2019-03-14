/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { DropDownItemMetas, DropDownItemMeta } from "../flyouts";
import Translate from "../content/Translate";
import DateTime from "../content/DateTime";
import ProfileLink from "../navigation/ProfileLink";
import { IUserFragment } from "../@types/api";
import { metasClasses } from "../styles/metasStyles";

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
