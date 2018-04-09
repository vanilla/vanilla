import uniqueid from "lodash/uniqueid";

export interface IComponentID {
    parentID?: string;
    ID?: string;
}

export function getUniqueID(props:IComponentID, uniqueSuffix:string):any {
    if ((!props.ID && !props.parentID) && (props.ID && props.parentID)) {
        throw new Error(`You must have *either* ID or parentID`);
    }

    if (props.parentID) {
        return props.parentID + "-" + uniqueSuffix + uniqueid();
    } else {
        return props.ID as string;
    }
}
