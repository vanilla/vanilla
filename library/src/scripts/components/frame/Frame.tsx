/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import FrameHeader from "@library/components/frame/FrameHeader";
import FrameFooter from "@library/components/frame/FrameFooter";
import FrameBody from "@library/components/frame/FrameBody";
import CompoundComponent from "@knowledge/layouts/CompoundComponent";

interface IProps {
    className?: string;
    children: React.ReactNode;
}

/**
 * Generic "frame" component. A frame is our generic term for flyouts or modals,
 * since they often use similar components.
 *
 * @example
 *  <Frame>
 *      <Frame.Header>
 *          Your header contents
 *      </Frame.Header>
 *      <Frame.body>
 *          <Frame.Panel>
 *              Your main content here
 *          </Frame.Panel>
 *      </Frame.body>
 *      <Frame.Footer>
 *          Your footer contents
 *      </Frame.Footer>
 * </PanelLayout>
 *
 */
export default class Frame extends CompoundComponent<IProps> {
    public static Header = FrameHeader;
    public static Footer  = FrameFooter;
    public static Body = FrameBody;

    public render() {
        const children = this.getParsedChildren();


        return (
            <section className={classNames('frame', this.props.className)}>
                {children.header && (
                    <FrameHeader>
                        {children.header}
                    </FrameHeader>
                )}


                {this.props.children}
            </section>
        );
    }


    /**
     * Parse out a specific subset of children. This is fast enough,
     * but should not be called more than once per render.
     */
    private getParsedChildren() {
        let header: React.ReactNode = null;
        let footer: React.ReactNode = null;
        let body: React.ReactNode = null;

        React.Children.forEach(this.props.children, child => {
            switch (true) {
                case this.childIsOfType(child, Frame.Header):
                    header = child;
                    break;
                case this.childIsOfType(child, Frame.Body):
                    body = child;
                    break;
                case this.childIsOfType(child, Frame.Footer):
                    footer = child;
                    break;
            }
        });

        return {
            header,
            body,
            footer,
        };
    }
}


// The components that make up the Layout itself.
interface IPanelItemProps {
    children?: React.ReactNode;
}

export function Header(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}

export function Body(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}

export function Footer(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}
