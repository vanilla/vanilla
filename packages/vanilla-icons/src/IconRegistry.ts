/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IconComponentType } from "./IconComponentType";
import { IconType } from "./IconType";

type IconStore = Partial<Record<IconType, IconComponentType>>;

class IconRegistry {
    private icons: IconStore = {};

    public baseUrl: string = "";

    public getIcon(iconType: IconType): IconComponentType | null {
        return this.icons[iconType] ?? null;
    }

    public registerIcon(iconType: IconType, icon: IconComponentType | { default: IconComponentType }) {
        this.icons[iconType] = "default" in icon ? icon.default : icon;
    }

    public getAllIcons(): IconStore {
        return this.icons;
    }
}

// Singleton.
const iconRegistry = new IconRegistry();

const isWebpack = process.env.IS_WEBPACK ?? false;
if (isWebpack) {
    iconRegistry.registerIcon("discussion-bookmark", require("../icons/discussion-bookmark.svg"));
    iconRegistry.registerIcon("discussion-bookmark-solid", require("../icons/discussion-bookmark-solid.svg"));
    iconRegistry.registerIcon("logo-jira", require("../icons/logo-jira.svg"));
}

export { iconRegistry };
