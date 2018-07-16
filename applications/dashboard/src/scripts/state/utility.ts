/**
 * State utility functions.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

export interface IAction<T extends string> {
    type: T;
}

export interface IActionWithPayload<T extends string, P> extends IAction<T> {
    payload: P;
}

export interface IActionCreator<T extends string> {
    (): IAction<T>;
}

export interface IActionWithPayloadCreator<T extends string, P> {
    (payload: P): IActionWithPayload<T, P>;
}

export function createAction<T extends string>(type: T): IAction<T>;
export function createAction<T extends string, P>(type: T, payload: P): IActionWithPayload<T, P>;
export function createAction<T extends string, P>(type: T, payload?: P) {
    return payload === undefined ? { type } : { type, payload };
}

export function actionCreator<T extends string>(type: T): IActionCreator<T>;
export function actionCreator<T extends string, P>(type: T): IActionWithPayloadCreator<T, P>;
export function actionCreator<T extends string, P>(type: T) {
    return (payload?: P) => createAction(type, payload)
}

type FunctionType = (...args: any[]) => any;
interface IActionCreatorsMapObject {
    [actionCreator: string]: FunctionType;
}

export type ActionsUnion<A extends IActionCreatorsMapObject> = ReturnType<A[keyof A]>;
