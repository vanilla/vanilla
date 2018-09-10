/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import WrapperBlot from "@rich-editor/quill/blots/abstract/WrapperBlot";
import { Blot, Container } from "quill/core";
import Parchment from "parchment";

export interface IWrappable extends Container {
    getWrapper(recursively?: boolean): WrapperBlot;
}

/**
 * Higher-order function to create a "wrapped" blot.
 *
 * Takes an existing Blot class and implements methods necessary to properly instantiate and cleanup it's parent Blot.
 * the passed Blot class must implement the static property parentName, which should reference a register Blot that is
 * and instance of WrapperBlot.
 *
 * @param blotConstructor - The Blot constructor to wrap.
 */
export default function withWrapper(blotConstructor: typeof Container) {
    class BlotWithWrapper extends blotConstructor {
        public parent: WrapperBlot;

        constructor(domNode) {
            super(domNode);
            if (!this.statics.parentName) {
                throw new Error("Attempted to instantiate wrapped Blot without setting static value parentName");
            }
        }

        public attach() {
            super.attach();
            if ((this.parent as any).statics.blotName !== this.statics.parentName) {
                const Wrapper = Parchment.create(this.statics.parentName);

                if (!(Wrapper instanceof WrapperBlot)) {
                    throw new Error("The provided static parentName did not instantiate an instance of a WrapperBlot.");
                }

                this.wrap(Wrapper);
            }
        }

        /**
         * If this is the only child blot we want to delete the parent with it.
         */
        public remove() {
            if (this.prev == null && this.next == null) {
                this.parent.remove();
            } else {
                super.remove();
            }
        }

        /**
         * Delete this blot it has no children. Wrap it if it doesn't have it's proper parent name.
         *
         * @param context - A shared context that is passed through all updated Blots.
         */
        public optimize(context) {
            super.optimize(context);
            if (this.children.length === 0) {
                this.remove();
            }
        }

        /**
         * @returns The parent blot of this Blot.
         */
        public getWrapper(recursively: boolean = false): WrapperBlot {
            const wrapper = this.parent as IWrappable;

            if (recursively && wrapper.getWrapper) {
                return wrapper.getWrapper(true);
            } else {
                return wrapper;
            }
        }

        /**
         * Replace this blot with another blot.
         *
         * @param name - The name of the replacement Blot.
         * @param value - The value for the replacement Blot.
         */
        public replaceWith(name: any, value?: any): any {
            const topLevelWrapper = this.getWrapper(true);
            const immediateWrapper = this.parent;

            immediateWrapper.children.forEach(child => {
                if (child === this) {
                    (child as any).replaceWithIntoScroll(name, value, topLevelWrapper);
                } else {
                    child.insertInto(this.scroll, topLevelWrapper);
                }
            });
            topLevelWrapper.remove();
        }

        /**
         * Replace this ContainerBlot with another one.
         *
         * Then attach that new Blot to the scroll in before the passed insertBefore Blot.
         * This is needed because we a normal replaceWith doesn't work (cyclicly recreates it's parents).
         *
         * @param name - The name of the Blot to replace this one with.
         * @param value - The initial value of the new blot.
         * @param insertBefore - The Blot to insert this blot before in the ScrollBlot.
         */
        public replaceWithIntoScroll(name: string, value: string, insertBefore: Blot) {
            const newBlot = Parchment.create(name, value) as Container;
            this.moveChildren(newBlot);

            newBlot.insertInto(this.scroll, insertBefore);
        }
    }

    return BlotWithWrapper;
}
