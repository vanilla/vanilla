/**
 * Adapted from https://github.com/reach/reach-ui/blob/develop/packages/utils/src/polymorphic.ts
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ElementType } from "react";

declare type Merge<P1 = {}, P2 = {}> = Omit<P1, keyof P2> & P2;
declare type MergeProps<E, P = {}> = P & Merge<E extends React.ElementType ? React.ComponentPropsWithRef<E> : never, P>;
/**
 * Infers `OwnProps` if E is a ForwardRefComponent
 */
declare type OwnProps<E> = E extends ForwardRefComponent<any, infer P> ? P : {};
/**
 * Infers the JSX.IntrinsicElement if E is a ForwardRefComponent
 */
declare type IntrinsicElement<E> = E extends ForwardRefComponent<infer I, any> ? I : never;
declare type NarrowIntrinsic<E> = E extends ElementType ? E : never;
/**
 * Extends original type to ensure built in React types play nice with
 * polymorphic components still e.g. `React.ElementRef` etc.
 */
interface ForwardRefComponent<IntrinsicElementString, OwnProps = {}>
    extends React.ForwardRefExoticComponent<
        MergeProps<
            IntrinsicElementString,
            OwnProps & {
                as?: IntrinsicElementString;
            }
        >
    > {
    <As extends ElementType = NarrowIntrinsic<IntrinsicElementString>>(
        props: MergeProps<
            As,
            OwnProps & {
                as: As;
            }
        >,
    ): React.ReactElement | null;
    /*
     * When passing an `as` prop as a string, use this overload. Merges original
     * own props (without DOM props) and the inferred props from `as` element with
     * the own props taking precendence.
     *
     * We explicitly define a `JSX.IntrinsicElements` overload so that events are
     * typed for consumers.
     */
    <As extends ElementType = NarrowIntrinsic<IntrinsicElementString>>(
        props: MergeProps<As, OwnProps & { as: As }>,
    ): React.ReactElement | null;

    /**
     * When passing an `as` prop as a component, use this overload. Merges
     * original own props (without DOM props) and the inferred props from `as`
     * element with the own props taking precendence.
     *
     * We don't use `React.ComponentType` here as we get type errors when
     * consumers try to do inline `as` components.
     */
    <As extends React.ElementType>(props: MergeProps<As, OwnProps & { as: As }>): React.ReactElement | null;
}

export type { ForwardRefComponent, OwnProps, IntrinsicElement, Merge };
