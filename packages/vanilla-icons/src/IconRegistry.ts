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
    const iconContext = require.context("../icons/", false, /.*\.svg/);
    iconContext.keys().forEach((key: string) => {
        // Trim down the key.
        const trimmedKey = key.replace("./", "").replace(".svg", "");

        iconRegistry.registerIcon(trimmedKey as IconType, iconContext(key));
    });
}

export { iconRegistry };
