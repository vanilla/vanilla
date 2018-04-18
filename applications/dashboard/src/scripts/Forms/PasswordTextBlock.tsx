import React from 'react';
import classNames from 'classnames';
import {IInputTextProps, default as InputTextBlock} from "./InputTextBlock";
import { t } from '@core/application';
import {uniqueIDFromPrefix, getOptionalID, IOptionalComponentID} from '@core/Interfaces/componentIDs';

export default class PasswordTextBlock extends React.Component<IInputTextProps> {
    public static defaultProps = {
        id: false,
    };

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
