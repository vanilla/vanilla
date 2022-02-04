import { getComponent } from "@library/utility/componentRegistry";
import React from "react";
import { IWidgetOptions, IWidgetResolver } from "@vanilla/react-utils/src";

/**
 * A single dynamic component (i.e. a widget).
 * @param props
 * @constructor
 */
export function Widget(props: IWidgetOptions) {
    const widget = getComponent(props.$type ?? "");

    if (widget) {
        const Component = widget.Component;
        const widgetProps = widget?.mountOptions?.widgetResolver
            ? widget.mountOptions.widgetResolver(props)
            : resolveWidgetProps(props);

        return <Component {...widgetProps} />;
    } else {
        // Mount the not found component instead.
        // This should be registered in the registry and just fall back to nothing if not registered.
        return <>Not found</>;
    }
}

/**
 * A container for one or more widgets.
 * @param components
 * @constructor
 */
export function WidgetContainer({ components }: { components: IWidgetOptions[] }) {
    const widgets = resolveComponents(components);
    return <>{widgets}</>;
}

/**
 * The default resolver for widget props. This can be used by other custom resolvers.
 * @param options
 */
export function resolveWidgetProps(options: IWidgetOptions): AnyObject {
    let props: AnyObject = Object.assign({}, options ?? {});
    delete props.$type;

    if (props.children) {
        props.children = resolveComponents(props.children);
    }
    return props;
}

/**
 * Resolve one or more widgets.
 * @param components
 */
export function resolveComponents(components?: IWidgetOptions | IWidgetOptions[]): React.ReactNode {
    if (!components) {
        return null;
    }
    if (!Array.isArray(components)) {
        components = [components];
    }
    return components.map((options: IWidgetOptions, i: number) => <Widget key={i} {...options} />);
}

export function resolveComponentsByKey(obj: AnyObject, keys: string[]): AnyObject {
    let r = {};

    for (let key of Object.keys(obj)) {
        if (keys.includes(key)) {
            r[key] = resolveComponents(obj[key]);
        } else {
            r[key] = obj[key];
        }
    }

    return r;
}

/**
 * A higher order function used to construct a widget resolver that has one or more props that should be expanded into widgets.
 * @param componentKeys An array of prop keys that will get resolved to components.
 */
export function widgetPropsResolver(componentKeys: string[]): IWidgetResolver {
    return (options: IWidgetOptions): AnyObject => {
        let props = resolveWidgetProps(options);
        props = resolveComponentsByKey(props, componentKeys);

        return props;
    };
}

export interface IWidgetContainerProps {
    components: IWidgetOptions[];
}
