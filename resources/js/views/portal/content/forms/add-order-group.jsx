
import React from 'react';
import Button from '../../../../common/inputs/button';
import Spacer from '../../../../common/components/spacer';
import toastr from 'toastr';
import StateSelect from '../../../../common/inputs/state-select';
import Text from '../../../../common/inputs/text';
import Header from '../../../../common/fields/header';
import StreetAddress from '../../../../common/inputs/street-address';
import NumberInput from '../../../../common/inputs/number';
import IntegerInput from '../../../../common/inputs/integer';
import GoaBrand from '../../../../common/brand';
import Weight from '../../../../common/inputs/weight';

const DEFAULT_PRODUCT = {
    name: '',
    sku: '',
    quantity: '',
    weight: 0,
}

export default class AddOrder extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            name: '',
            email: '',
            company: '',
            phone: '',
            reference: '',
            street_1: '',
            street_2: '',
            city: '',
            state: '',
            postal: '',
            country: 'US',
            orderProducts: [{ ...DEFAULT_PRODUCT }]

        };

        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleAddProduct = this.handleAddProduct.bind(this);
        this.handleRemoveProduct = this.handleRemoveProduct.bind(this);
        this.handleAutoFill = this.handleAutofill.bind(this);
    }

    handleAddProduct(e) {
        e.stopPropagation()
        this.state.orderProducts.push({ ...DEFAULT_PRODUCT });
        this.forceUpdate();
    }

    handleRemoveProduct(i) {
        let index = (i - 1)
        let productArray = this.state.orderProducts
        productArray.splice(index, 1)
        this.setState({ orderProducts: productArray }, success => this.forceUpdate())
    }

    handleSubmit(e) {
        e.preventDefault();
        if (this.loading) return;
        this.loading = true;
        GoaApi.OrderGroup.add({
            name: this.state.name,
            company: this.state.company,
            phone: this.state.phone,
            email: this.state.email,
            orders: [{
                reference: this.state.reference,
                order_products: this.state.orderProducts
            }],
            address: {
                name: this.state.name,
                company: this.state.company,
                phone: this.state.phone,
                email: this.state.email,
                street_1: this.state.street_1,
                street_2: this.state.street_2,
                city: this.state.city,
                postal: this.state.postal,
                state: this.state.state,
                country: this.state.country
            }
        }, this.props.onOrderGroupAdd, failure => {
            toastr.error(failure.message);
            this.loading = false;
        })
    }

    handleAutofill(e) {
        let newState = { ...this.state, ...e }
        this.setState(newState)
    }


    render() {

        let orderProducts = this.state.orderProducts.map((x, i) => <OrderProduct
            key={i}
            index={i + 1}
            orderProduct={x}
            onUpdate={() => this.forceUpdate()}
            removeProduct={this.handleRemoveProduct}
        />)

        return (
            <form onSubmit={this.handleSubmit}>
                <div>
                    <Header title='Details' top={true} />
                    <Spacer space='20px' />
                    <div style={STYLES.flexRow}>
                        <Text
                            autoFocus={true}
                            title='Customer Name'
                            onChange={e => this.setState({ name: e.target.value })}
                            value={this.state.name}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                        <Spacer space='10px' />
                        <Text
                            title='Reference'
                            onChange={e => this.setState({ reference: e.target.value })}
                            value={this.state.reference}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                        <Spacer space='10px' />
                        <Text
                            title='Company'
                            onChange={e => this.setState({ company: e.target.value })}
                            value={this.state.company}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                    </div>
                    <div style={STYLES.flexRow}>
                        <Text
                            title='Email'
                            onChange={e => this.setState({ email: e.target.value })}
                            value={this.state.email}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                        <Spacer space='10px' />
                        <Text
                            title='Phone'
                            onChange={e => this.setState({ phone: e.target.value })}
                            value={this.state.phone}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                    </div>
                    <Spacer space='40px' />
                    <Header title='Address' />
                    <Spacer space='20px' />

                    <div style={STYLES.flexRow}>
                        <StreetAddress
                            title='Street 1'
                            stylesinput={STYLES.textInput}
                            stylescontainer={STYLES.input}
                            styleslabel={STYLES.label}
                            value={this.state.street_1}
                            handleChange={e => this.setState({ street_1: e })}
                            handleAutofill={e => this.handleAutofill(e)}
                        />
                        <Spacer space='10px' />
                        <Text
                            title='Street 2'
                            onChange={e => this.setState({ street_2: e.target.value })}
                            value={this.state.street_2}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                    </div>
                    <div style={STYLES.flexRow}>
                        <Text
                            title='City'
                            onChange={e => this.setState({ city: e.target.value })}
                            value={this.state.city}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                        <Spacer space='10px' />
                        <StateSelect
                            value={this.state.state}
                            handleChange={e => this.setState({ state: e.target.value })}
                            styleslabel={STYLES.label}
                            stylesselect={STYLES.selectInput}
                        />
                        <Spacer space='10px' />
                        <Text
                            title='Postal'
                            onChange={e => this.setState({ postal: e.target.value })}
                            value={this.state.postal}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                    </div>

                    <Spacer space='40px' />
                    <Header title='Products' />
                    <Spacer space='20px' />
                    {orderProducts}
                    <div style={STYLES.flexRow}>
                        <Button
                            stylesbutton={STYLES.button2}
                            stylesbuttonhover={STYLES.buttonHover2}
                            props={{
                                onClick: this.handleAddProduct,
                                type: 'button'
                            }}
                        >
                            Add Product
                        </Button>
                        <Spacer space='20px' />
                        <Button
                            stylesbutton={STYLES.button}
                            stylesbuttonhover={STYLES.buttonHover}
                            props={{ type: 'submit' }}
                        >
                            Add Order
                        </Button>
                    </div>
                </div>
            </form>
        )
    }
}

const OrderProduct = (props) => {
    return (
        <div style={STYLES.productRow}>
            <div style={STYLES.flexRow}>
                {/* <IntegerInput
                    container={STYLES.numberinput}
                    stylesinput={STYLES.textInput}
                    styleslabel={STYLES.label}
                    label='Quantity'
                    showLabel={true}
                    value={props.orderProduct.quantity}
                    onChange={(e) => {
                        props.orderProduct.quantity = e;
                        props.onUpdate();
                    }}
                    min={1}
                /> */}
                <Text
                    autoFocus={props.index != 1}
                    title={`Product ${props.index}: Name`}
                    onChange={(e) => {
                        props.orderProduct.name = e.target.value;
                        props.onUpdate();
                    }}
                    value={props.orderProduct.name}
                    stylescontainer={STYLES.input}
                    stylesinput={STYLES.textInput}
                    styleslabel={STYLES.label}
                />
                <Spacer space='10px' />
                <Text
                    title='Quantity'
                    value={props.orderProduct.quantity}
                    onChange={(e) => {
                        props.orderProduct.quantity = e.target.value;
                        props.onUpdate();
                    }}
                    stylescontainer={STYLES.input}
                    stylesinput={STYLES.textInput}
                    styleslabel={STYLES.label}
                />
                <Spacer space='10px' />
                <Text
                    title='Sku'
                    onChange={(e) => {
                        props.orderProduct.sku = e.target.value;
                        props.onUpdate();
                    }}
                    value={props.orderProduct.sku}
                    stylescontainer={STYLES.input}
                    stylesinput={STYLES.textInput}
                    styleslabel={STYLES.label}
                />
                <Spacer space='10px' />
                <Weight
                    value={props.orderProduct.weight}
                    onChange={(value) => {
                        props.orderProduct.weight = value;
                        props.onUpdate();
                    }}
                    stylesinput={STYLES.textInput}
                    styleslabel={STYLES.label}
                />

            </div>
            <i style={STYLES.icon} className={'fa fa-trash'} onClick={() => props.removeProduct(props.index)} />
        </div>
    )
}

const STYLES = {
    flexRow: {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-around'
    },
    productRow: {
        display: 'flex',
        alignItems: 'center',
    },
    input: {
        flex: 1,
        minHeight: '36px'
    },
    numberinput: {
        flex: 1,
    },
    label: {
        marginBottom: '2px',
        fontSize: '16px',
        fontWeight: 'bold'
    },
    icon: {
        cursor: 'pointer',
        marginLeft: '10px',
        fontSize: '18px',
        marginTop: '10px'
    },
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
    button: {
        height: '50px',
        borderRadius: '20px',
        color: 'white',
        backgroundColor: GoaBrand.getPrimaryColor()
    },
    buttonHover: {
        backgroundColor: GoaBrand.getPrimaryHoverColor()
    },
    button2: {
        height: '50px',
        backgroundColor: 'transparent',
        borderRadius: '20px',
    },
    buttonHover2: {
        backgroundColor: GoaBrand.getPrimaryHoverColor(),
        color: 'white'

    }

}