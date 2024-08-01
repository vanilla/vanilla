/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalValueRef } from "@vanilla/utils";
import { IconComponentType } from "./IconComponentType";
import { IconType } from "./IconType";

type IconStore = Partial<Record<IconType, IconComponentType>>;

class IconRegistry {
    private icons = globalValueRef<IconStore>("iconStore", {});

    public baseUrl: string = "";

    public getIcon(iconType: IconType): IconComponentType | null {
        return this.icons.current()[iconType] ?? null;
    }

    public registerIcon(iconType: IconType, icon: IconComponentType | { default: IconComponentType }) {
        this.icons.current()[iconType] = "default" in icon ? icon.default : icon;
    }

    public getAllIcons(): IconStore {
        return this.icons.current();
    }
}

// Singleton.
const iconRegistry = new IconRegistry();

export { iconRegistry };
