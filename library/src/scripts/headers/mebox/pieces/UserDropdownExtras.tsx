/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

/** Hold the extra user dropdown menu components before rendering. */
export const extraUserDropDownComponents: Array<React.ComponentType<IExtraDropDownProps>> = [];

interface IExtraDropDownProps {
    getCountByName: (name: string) => number;
}

/**
 * Register an extra component to be rendered before the user dropdown menu.
 * This will only affect larger screen sizes.
 *
 * @param component The component class to be render.
 */
export const registerBeforeUserDropDown = (component: React.ComponentType<IExtraDropDownProps>) => {
    extraUserDropDownComponents.push(component);
};
