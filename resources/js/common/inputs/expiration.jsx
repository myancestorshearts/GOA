
import React from 'react';
import Text from './text';

export default class Expiration extends React.Component {


    constructor(props) {
        super(props);

        this.state = {
        }

        this.handleExpiration = this.handleExpiration.bind(this);
    }
    
    handleExpiration(e) {
        let filteredValue = e.target.value.replace(/[^0-9.]/g, "");
        if (filteredValue.length > 4) filteredValue = filteredValue.substring(0, 4);
        if (this.props.onChange) this.props.onChange(filteredValue);
    }

    render() {
        let showValue = this.props.value.length > 2 ? this.props.value.insert(2, '-') : this.props.value;
        return <Text value={showValue} onChange={this.handleExpiration} title={this.props.title} placeholder={this.props.placeholder} autoComplete={this.props.autoComplete}/>
    }
}

