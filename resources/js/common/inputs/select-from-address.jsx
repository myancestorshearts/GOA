import SelectModel from './select-model';
import React from 'react';

import GoaEvent from '../goa-event';

import Functions from '../functions';


export default class SelectPackage extends React.Component {

    constructor(props) {
        super(props)
        
        this.state = {
            loading: true,
            addresses: [],
            address: undefined
        }

        this.handleLoadAddresses = this.handleLoadAddresses.bind(this);
        this.handleChange = this.handleChange.bind(this);
    }

    componentDidMount() {
        this.handleLoadAddresses();

        this.eventKey = GoaEvent.subscribe('address-refresh', this.handleLoadAddresses)
    }

    componentWillUnmount() {
        GoaEvent.unsubscribe('address-refresh', this.eventKey);
    }

    handleLoadAddresses() {
        if (this.state.package != undefined) this.handleChange(undefined);
        this.setState({
            loading: true,
            addresses: []
        }, () => {


            GoaApi.Address.search({ take: 1000, page: 1 }, success => {
                let defaultAddress = success.data.models.find(x => Functions.inputToBool(x.default));

                // when I add the custom from address options
                //success.data.models.push({id: 'CUSTOM', name: 'Custom', type: 'Parcel', length: '', width: '', height: ''});

                this.setState({
                    addresses: success.data.models,
                    address: defaultAddress,
                    loading: false
                }, () => {
                    if (this.props.onChange) this.props.onChange(defaultAddress)
                })
            })
        });
    }

    handleChange(model) {
       
        this.setState({
            address: model,
            
        })

        if (this.props.onChange) {
            this.props.onChange(model);
        }
    }

    


    render() {
        return (

            <SelectModel
                placeholderLoading='Loading Addresses'
                placeholder='Select Address'
                loading={this.state.loading}
                title='From Address'
                models={this.state.addresses}
                value={this.state.address}
                onChange={this.handleChange}
                styleslabel={this.props.styleslabel}
                stylesselect={this.props.stylesinput}
                color={this.props.color}
                methodLabel={x => x.name + ' - ' + Functions.formatAddress(x)}
            />

        )
    }
}


const STYLES = {
    flexRow: {
        display: 'flex',
        alignItems: 'center'
    },
    textInput: {
        fontWeight: 'Bold',
        fontSize: '15px',
        color: '#555'
    },
    textContainer: {
        flex: 1,
        minWidth: '50px'
    },
    sizeLabels: {
        fontSize: '12px'
    }
}