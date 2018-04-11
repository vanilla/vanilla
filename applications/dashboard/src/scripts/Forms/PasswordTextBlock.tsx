import React from 'react';
import classNames from 'classnames';
import {IInputTextProps, default as InputTextBlock} from "./InputTextBlock";
import { t } from '@core/application';
import {uniqueID, IComponentID} from '@core/Interfaces/componentIDs';

export default class PasswordTextBlock extends React.Component<IInputTextProps> {
    private type: string;
    private placeholder: string;

    constructor(props) {
        super(props);
        this.placeholder = props.placeholder || t('Enter Password');
    }

    public render() {
        return <InputTextBlock {...this.props} type="password" />;
    }
}
