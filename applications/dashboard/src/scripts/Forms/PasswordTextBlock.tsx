import React from 'react';
import classNames from 'classnames';
import InputTextBlock from "./InputTextBlock";
import UniqueID from "react-html-id";
import { t } from '@core/application';

interface IProps {
    parentID: string;
    className?: string;
    label: string;
    labelNote?: string;
    inputClassNames?: string;
    labelID?: string;
    value?: string;
    placeholder?: string;
    valid?: boolean;
    descriptionID?: string;
    required?: boolean;
    errors?: string[];
    disabled?: boolean;
}

export default class PasswordTextBlock extends React.Component<IProps> {
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
