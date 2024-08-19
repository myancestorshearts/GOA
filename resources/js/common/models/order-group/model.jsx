import React from 'react';
import Header from '../../fields/header';
import Label from '../../fields/label';
import Spacer from '../../components/spacer';
import Functions from '../../functions';
import InputText from '../../inputs/text';
import Button from '../../inputs/button';
import SelectModel from '../../inputs/select-model';
import toastr from 'toastr';
import GoaBrand from '../../brand';
import TrackingLink from '../label/tracking-link';
import ActiveLabel from '../label/model';
import Modal from '../../portal/modal';
import Weight from '../../inputs/weight';
import SelectPackage from '../../inputs/select-package';
import SelectFromAddress from '../../inputs/select-from-address';
import LabelModelMass from '../label/model-mass';
import GoaState from '../../goa-state';
import ComponentLoading from '../../components/loading';

import InputBoolean from '../../../common/inputs/boolean';

export default class Model extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            weight: this.props.model.weight ? this.props.model.weight : 0,
            address: undefined,
            package: undefined,
            shipment: undefined,
            rate: undefined,
            rates: [],
            shipmentLoading: false,
            purchasing: false,
            insurance: false,
            contents_value: ''
        }

        this.getRate = this.getRate.bind(this);
        this.handlePurchase = this.handlePurchase.bind(this);
        this.rateIndex = 0;
    }

    getRate() {
        if (this.shipmentLoading) return;

        let rateIndex = ++this.rateIndex;
        this.setState({
            rate: undefined,
            shipment: undefined,
            shipmentLoading: true
        }, () => {

            let rateArgs = {
                weight: this.state.weight,
                from_address_id: this.state.address.id,
                package: this.state.package,
                order_group_id: this.props.model.id
            }

            if (this.state.insurance) {
                rateArgs.services = ['INSURANCE'];
                rateArgs.contents_value = this.state.contents_value
            }

            console.log(rateArgs);

            GoaApi.Shipment.rate(rateArgs, success => {
                // protects users from showing incorrect rates
                if (rateIndex != this.rateIndex) return;

                let rates = success.data.model.rates.map(x => ({
                    price: x.total_charge,
                    name: x.service + ' - ' + x.delivery_days + ' Day(s) - ' + Functions.convertToMoney(x.total_charge),
                    id: x.id
                }))
                this.setState({ shipment: success.data.model, rates: rates, shipmentLoading: false })
            }, failure => {
                this.setState({ shipment: null, shipmentLoading: false })
                toastr.error(failure.message);
            })
        })
    }

    componentDidUpdate(prevProps) {
        if (prevProps.model.id != this.props.model.id) {
            if (this.weightInput) this.weightInput.handleUpdateValue(this.props.model.weight ? this.props.model.weight : 0)
            this.setState({
                weight: this.props.model.weight ? this.props.model.weight : 0,
                shipment: undefined,
                rate: undefined
            })
        }
    }

    handlePurchase() {

        if (this.state.purchasing) return false;

        this.setState({
            purchasing: true
        }, () => {

            GoaApi.Label.purchase({
                shipment_id: this.state.shipment.id,
                rate_id: this.state.rate.id
            }, success => {

                // add labels to the model 
                if (!this.props.model.labels) this.props.model.labels = [];
                this.props.model.labels.push(success.data.model)

                // set purchase label fields and set active label to show printed label
                this.setState({
                    shipment: undefined,
                    rate: undefined
                });

                GoaState.set('active-modal', {
                    component: <LabelModelMass
                        labels={[success.data.model]}
                        onClose={() => GoaState.empty('active-modal')}
                    />
                });

                GoaState.set('active-model', { component: null, model: null })

                // update wallet balance on user
                let newBalance = (success.data.refill_transaction && !Functions.inputToBool(success.data.refill_transaction.pending)) ?
                    success.data.refill_transaction.balance : success.data.wallet_transaction.balance;
                GoaUser.updateUser({ wallet_balance: newBalance });

                // remove purchasing so we can run api again
                this.purchasing = false;

                if (this.props.onPurchase) this.props.onPurchase();


            }, failure => {
                toastr.error(failure.message);
                this.setState({ purchasing: false });
            })
        })
    }

    render() {
        let model = this.props.model;

        let labels = model.labels ? model.labels.map((x, i) => <TrackingLink
            key={i}
            label={x}
        />) : []

        let orders = model.orders ? model.orders.map((x, i) => <Order
            key={i}
            order={x}
            index={i + 1}
            multiple={model.orders.length > 1}
        />) : null

        return (
            <React.Fragment>
                <Header title='Details' top={true} />
                <Spacer space='10px' />

                <Label label='Customer Name'>
                    {Functions.deepGetFromString(model, 'name', '')}
                </Label>
                {
                    !Functions.isEmpty(model.company) ?
                        <Label label='Company'>
                            {Functions.deepGetFromString(model, 'company', '')}
                        </Label> : null
                }
                {
                    !Functions.isEmpty(model.company) ?
                        <Label label='Email'>
                            {Functions.deepGetFromString(model, 'email', '')}
                        </Label> : null
                }
                {
                    !Functions.isEmpty(model.phone) ?
                        <Label label='Phone'>
                            {Functions.deepGetFromString(model, 'phone', '')}
                        </Label> : null
                }

                <Label label='Shipping Address'>
                    {Functions.deepGetFromString(model, 'address,name')} <br />
                    {Functions.deepGetFromString(model, 'address,street_1')} {Functions.deepGetFromString(model, 'address,street_2')} <br />
                    {Functions.deepGetFromString(model, 'address,city')}, {Functions.deepGetFromString(model, 'address,state')} <br />
                    {Functions.deepGetFromString(model, 'address,postal')}
                </Label>

                {/* Loop through orders */}
                {orders}

                {/* Loop through labels if there are any purchased */}
                {
                    labels.length > 0 ? <React.Fragment>
                        <Spacer space='10px' />
                        <Header title='Purchased Labels' />
                        <Spacer space='10px' />

                        <Label label='Tracking Numbers'>
                            {labels}
                        </Label>
                    </React.Fragment> : null
                }

                <Header title='Label' />
                <Spacer space='10px' />

                {/* From Address */}
                <SelectFromAddress
                    onChange={x => this.setState({ address: x, shipment: null, shipmentLoading: false })}
                    color='rgb(175, 175, 175)'
                    stylesinput={STYLES.selectInput}
                    styleslabel={STYLES.label}
                />

                <Weight
                    ref={e => this.weightInput = e}
                    value={this.state.weight}
                    onChange={x => this.setState({ weight: x, shipment: null, shipmentLoading: false })}
                    color='rgb(175, 175, 175)'
                    stylesinput={STYLES.textInput}
                    styleslabel={STYLES.label}
                />



                <Label label='Insurance'>
                    <InputBoolean title='' model={this.state} property='insurance' onChange={() => this.setState({
                        shipment: null,
                        shipmentLoading: false
                    })} />
                </Label>

                {
                    this.state.insurance ?
                        <InputText
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                            title='Amount To Insure'
                            value={this.state.contents_value}
                            onChange={x => this.setState({ contents_value: x.target.value, shipment: null, shipmentLoading: false })}
                        /> : null
                }

                {/* Package */}
                <SelectPackage
                    onChange={x => this.setState({ package: x, shipment: null, shipmentLoading: false })}
                    color='rgb(175, 175, 175)'
                    stylesselect={STYLES.selectInput}
                    stylestext={STYLES.textInput}
                    styleslabel={STYLES.label}
                />
                {
                    this.state.shipmentLoading || this.state.shipment ?
                        <ComponentLoading loading={this.state.shipmentLoading}>
                            <React.Fragment>
                                {/* Service */}
                                <SelectModel
                                    placeholderLoading='Loading Rates'
                                    placeholder={this.state.shipment ? 'Select Rate' : 'Fill out all required fields'}
                                    disabled={!this.state.shipment}
                                    loading={this.state.shipmentLoading}
                                    title='Rate'
                                    models={this.state.rates}
                                    value={this.state.rate}
                                    onChange={x => this.setState({ rate: x })}
                                    color='rgb(175, 175, 175)'
                                    stylesselect={STYLES.selectInput}
                                    styleslabel={STYLES.label}
                                />

                                {/* Purchase Button */}
                                {
                                    this.state.rate ?
                                        <ComponentLoading loading={this.state.purchasing}>
                                            <Button
                                                color={GoaBrand.getPrimaryColor()}
                                                stylesbutton={STYLES.purchaseButton}
                                                stylesbuttonhover={STYLES.buttonHover}
                                                props={{ onClick: this.handlePurchase }}
                                            >
                                                Purchase Label
                                            </Button>
                                        </ComponentLoading> : null
                                }
                            </React.Fragment>
                        </ComponentLoading> :
                        <Button
                            props={{
                                type: 'button',
                                onClick: this.getRate
                            }}
                            color={GoaBrand.getPrimaryColor()}
                            stylesbutton={STYLES.purchaseButton}
                            stylesbuttonhover={STYLES.buttonHover}
                        >
                            Rate
                        </Button>
                }
            </React.Fragment>
        )
    }
}


const Order = (props) => {
    let order = props.order;
    let label = 'Order';
    if (!Functions.isEmpty(order.reference)) label = `Order: ${order.reference}`
    else if (props.multiple) label = `Order: ${props.index}`

    let orderProducts = order.order_products ? order.order_products.map((x, i) => <OrderProduct
        key={i}
        orderProduct={x}
        index={i + 1}
    />) : null

    return (
        <React.Fragment>
            <Spacer space='10px' />
            <Header title={label} />
            <Spacer space='10px' />
            {orderProducts}
        </React.Fragment>
    )
}

const OrderProduct = (props) => {

    let orderProduct = props.orderProduct

    return (
        <Label>
            <span style={STYLES.label}>Product:</span> {orderProduct.name}<br />
            <span style={STYLES.label}>Qty:</span> {orderProduct.quantity}<br />
            <span style={STYLES.label}>Sku:</span> {orderProduct.sku}
            <Spacer space='10px' />
        </Label>
    )
}


const STYLES = {
    textInput: {
        fontWeight: '600',
        fontFamily: 'poppins',
        fontSize: '18px',
        color: '#273240',
        borderRadius: '20px',
        height: '44px',
        borderColor: '#96A0AF'
    },
    selectInput: {
        fontWeight: '600',
        fontFamily: 'poppins',
        fontSize: '18px',
        color: '#273240',
        borderRadius: '20px',
        height: '50px',
        borderColor: '#96A0AF'
    },
    label: {
        fontFamily: 'poppins',
        fontWeight: '600',
        fontSize: '12px',
        color: '#96A0AF'
    },
    purchaseButton: {
        height: '50px',
        marginTop: '20px',
        borderRadius: '20px'
    },
    buttonHover: {
        backgroundColor: GoaBrand.getPrimaryHoverColor()
    }
}