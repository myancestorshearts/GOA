
import React from 'react';

import Text from '../../../../common/inputs/text';
import Button from '../../../../common/inputs/button';
import Spacer from '../../../../common/components/spacer';
import toastr from 'toastr';
import Select from '../../../../common/inputs/select';
import GoaBrand from '../../../../common/brand';

const INPUTS = {
    cc: [
        {
            label: 'Name',
            property: 'name',
            autofocus: true
        },
        {
            label: 'Credit Card Number',
            property: 'card'
        },
        {
            label: 'Expiration',
            property: 'expiration',
            placeholder: 'mm-yy'
        },
        {
            label: 'Security Code',
            property: 'security'
        },
        {
            label: 'Zipcode',
            property: 'zipcode'
        }
    ],
    ach: [
        {
            label: 'Type',
            type: 'select',
            options: [
                {
                    label: 'Personal Checking',
                    value: 'C'
                },
                {
                    label: 'Personal Savings',
                    value: 'S'
                },
                {
                    label: 'Business Checking',
                    value: 'X'
                }
            ],
            property: 'account_type',
            default: 'C'
        },
        {
            label: 'Account',
            property: 'account',
            autofocus: true
        },
        {
            label: 'Account Confirm',
            property: 'accountConfirm'
        },
        {
            label: 'Routing',
            property: 'routing'
        },
        {
            label: 'Routing Confirm',
            property: 'routingConfirm'
        },
        {
            label: 'Name on Account',
            property: 'name'
        },
        {
            label: 'Zipcode',
            property: 'zipcode'
        }
    ]
};

export default class Payment extends React.Component {

    constructor(props) {
        super(props);
        this.state = {};

        this.inputs = INPUTS[this.props.method];

        this.inputs.forEach(x => this.state[x.property] = x.default ? x.default : '');
        this.handleSubmit = this.handleSubmit.bind(this);
    }

    handleSubmit(e) {
        e.preventDefault();
    
        let args = {
            type: this.props.method,
            name: this.state.name,
            zipcode: this.state.zipcode,
        }

        if (this.props.method == 'ach') {
            if (this.state.accountConfirm != this.state.account) {
                toastr.warning('Account numbers do not match')
                return;
            }
            if (this.state.routingConfirm != this.state.routing) {
                toastr.warning('Routing numbers do not match')
                return;
            }
            args.account_type = this.state.account_type;
            args.account = this.state.account;
            args.routing = this.state.routing;
        }
        else {
            args.card = this.state.card;
            args.security = this.state.security;
            args.expiration_month = this.state.expiration.replace('-', '').substring(0, 2);
            args.expiration_year = this.state.expiration.replace('-', '').substring(2, 4);
        }

        GoaApi.PaymentMethod.add(args, success => {
            this.props.onAdd(success.data.payment_method)
        }, failure => {
            toastr.error(failure.message);
        })
    }

    render() {

        let inputs = this.inputs.map(x => {
        
            return (x.type == 'select') ?
                <Select
                key={x.property}
                title={x.label}
                options={x.options}
                value={this.state[x.property]}
                onChange={e => this.setState({[x.property]: e.target.value})}
            /> : <Text
                autofocus={x.autofocus}
                key={x.property}
                title={x.label}
                autoComplete={x.property}
                value={this.state[x.property]}
                placeHolder={x.placeholder}
                onChange={e => this.setState({[x.property]: e.target.value})}
            />
            
        });

        return (
            <form onSubmit={this.handleSubmit}>
                {inputs}
                <Spacer space='10px'/>
                <div style={STYLES.confirmButtonsContainer}>
                    <Button 
                        props={{type: 'submit'}}
                        color={GoaBrand.getPrimaryColor()}
                    >
                        Add Payment
                    </Button>
                    <Spacer space='20px'/>
                    <Button 
                        props={{
                            type: 'button',
                            onClick: this.props.onCancel
                        }}
                        color={GoaBrand.getPrimaryColor()}
                    >
                        Cancel
                    </Button>
                </div>
            </form>
        )
    }
}

const STYLES = {
    confirmButtonsContainer: {
        display: 'flex'
    }
}