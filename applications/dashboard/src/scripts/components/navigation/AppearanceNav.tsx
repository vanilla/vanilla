/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AdminNav } from "@dashboard/components/navigation/AdminNav";
import { useAppearanceNavItems } from "@dashboard/components/navigation/AppearanceNav.hooks";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import appearanceNavClasses from "./AppearanceNav.classes";

interface IProps {
    asHamburger?: boolean;
}

export function AppearanceNav(props: IProps) {
    const classes = appearanceNavClasses();
    const id = useUniqueID("AppearanceNav");

    const navItems = useAppearanceNavItems(id);

    return (
        <AdminNav
            sectionID={"appearance"}
            className={classes.root}
            title={t("Appearance")}
            id={id}
            navItems={navItems}
        />
    );
}
