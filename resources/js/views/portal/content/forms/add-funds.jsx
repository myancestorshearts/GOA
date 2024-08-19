
import React from 'react';
import CommonFunctions from '../../../../common/functions';
import Button from '../../../../common/inputs/button';
import Spacer from '../../../../common/components/spacer';
import toastr from 'toastr';
import Select from '../../../../common/inputs/select';
import Loading from '../../../../common/components/loading';
import Text from '../../../../common/inputs/text';
import GoaBrand from '../../../../common/brand';

export default class AddFunds extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            type: undefined,
            amount: '',
            achPaymentMethod: undefined,
            ccPaymentMethod: undefined,
            loading: true
        };

        this.handleSubmit = this.handleSubmit.bind(this);
    }

    componentDidMount() {
        GoaApi.PaymentMethod.all({}, success => {
            success.data.payment_methods.forEach(x => {
                if (x.type == 'ach') {
                    this.state.type = 'ach'
                    this.state.achPaymentMethod = x;
                }
                else if (x.type == 'cc') {
                    if (!this.state.type) this.state.type = 'cc';
                    this.state.ccPaymentMethod = x;
                }
            })
            this.setState({ loading: false })
        })
    }

    handleSubmit(e) {
        e.preventDefault();

        let processing_fee = this.state.type == 'cc' ? (this.state.amount * window.GOA_GATEWAY_CC_FEE) : 0;

        GoaApi.Wallet.refill({ amount: this.state.amount, type: this.state.type, processing_fee: processing_fee }, (success) => {
            if (success.data.wallet_transaction.balance) GoaUser.updateUser({ wallet_balance: success.data.wallet_transaction.balance });
            if (this.props.onAddedFunds) this.props.onAddedFunds();
        }, failure => {
            toastr.error(failure.message)
        })

    }

    render() {

        let options = [];
        if (this.state.achPaymentMethod) options.push(
            {
                label: 'Account ending in ' + this.state.achPaymentMethod.last_four,
                value: 'ach'
            })
        if (this.state.ccPaymentMethod) options.push(
            {
                label: 'Card ending in ' + this.state.ccPaymentMethod.last_four,
                value: 'cc'
            })
        let processing_fee = this.state.type == 'cc' ? (this.state.amount * window.GOA_GATEWAY_CC_FEE) : 0;
        let currency = CommonFunctions.convertToMoney(this.state.amount);
        return (
            <form onSubmit={this.handleSubmit}>
                <Loading loading={this.state.loading}>
                    {
                        options.length > 0 || window.GOA_ENVIRONMENT == 'sandbox'
                            ?
                            <div>
                                <Text
                                    title='Amount'
                                    onChange={e => this.setState({ amount: e.target.value })}
                                    value={this.state.amount}
                                    autoFocus={true}
                                />
                                {
                                    window.GOA_ENVIRONMENT != 'sandbox' ?
                                        <Select
                                            title='Type'
                                            options={options}
                                            value={this.state.type}
                                            onChange={e => this.setState({ type: e.target.value })}
                                        /> : null
                                }
                                <Spacer space='10px' />
                                <div>
                                    {'Are you sure you want to add ' + currency + ' to your wallet?'}
                                </div>
                                <Spacer space='5px' />
                                <div>
                                    {processing_fee > 0 ? 'Using your credit card will add a ' + CommonFunctions.convertToMoney(processing_fee) + ' processing fee to your order.' : null}
                                </div>
                                <Spacer space='10px' />
                                <div style={STYLES.confirmButtonsContainer}>
                                    <Button
                                        props={{ type: 'submit' }}
                                        color={GoaBrand.getPrimaryColor()}
                                    >
                                        Add Payment
                                    </Button>
                                    <Spacer space='20px' />
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
                            </div>
                            :
                            <div>
                                Add a Payment Method by clicking the user icon in the top right.
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
                    }
                </Loading>
            </form>
        )
    }
}

const STYLES = {
    confirmButtonsContainer: {
        display: 'flex'
    }
}