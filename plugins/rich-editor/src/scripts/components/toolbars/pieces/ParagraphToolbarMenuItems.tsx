/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import MenuItems from "@rich-editor/components/toolbars/pieces/MenuItems";
import MenuItem from "@rich-editor/components/toolbars/pieces/MenuItem";
import { t } from "@dashboard/application";
import * as icons from "../../icons";
import Formatter from "@rich-editor/quill/Formatter";
import { IFormats } from "quill/core";

interface IProps {
    formatter: Formatter;
    activeFormats: IFormats;
}

export default function ParagraphToolbarMenuItems(props: IProps) {
    const { formatter, activeFormats } = props;
    return (
        <MenuItems>
            {(firstItemRef, lastItemRef) => (
                <React.Fragment>
                    <MenuItem
                        ref={firstItemRef}
                        icon={icons.pilcrow()}
                        label={t("Format as Paragraph")}
                        onClick={formatter.paragraph}
                        isActive={false}
                    />
                    <MenuItem
                        icon={icons.title()}
                        label={t("Format as Title")}
                        onClick={formatter.h2}
                        isActive={activeFormats.header === 2}
                    />
                    <MenuItem
                        icon={icons.subtitle()}
                        label={t("Format as Subtitle")}
                        onClick={formatter.h3}
                        isActive={activeFormats.header === 3}
                    />
                    <MenuItem
                        icon={icons.blockquote()}
                        label={t("Format as blockquote")}
                        onClick={formatter.blockquote}
                        isActive={activeFormats["blockquote-line"] === true}
                    />
                    <MenuItem
                        icon={icons.codeBlock()}
                        label={t("Format as code block")}
                        onClick={formatter.codeBlock}
                        isActive={activeFormats.codeBlock === true}
                    />
                    <MenuItem
                        ref={lastItemRef}
                        icon={icons.spoiler()}
                        label={t("Format as spoiler")}
                        onClick={formatter.spoiler}
                        isActive={activeFormats["spoiler-line"] === true}
                    />
                </React.Fragment>
            )}
        </MenuItems>
    );
}
