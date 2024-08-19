
import React from 'react';
import Button from '../../../../common/inputs/button';
import Spacer from '../../../../common/components/spacer';
import toastr from 'toastr';
import StateSelect from '../../../../common/inputs/state-select';
import StreetAddress from '../../../../common/inputs/street-address';
import Text from '../../../../common/inputs/text';
import GoaBrand from '../../../../common/brand';
import Header from '../../../../common/fields/header';

export default class AddPackage extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            name: '',
            email: '',
            company: '',
            phone: '',
            street_1: '',
            street_2: '',
            city: '',
            state: '',
            postal: '',
            country: 'US',
            saved: 1,
            from: 1
        };

        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleAutoFill = this.handleAutofill.bind(this);
    }

    componentDidMount() {
        if (this.props.tour) {
            if (this.props.tour.isActive()) {
                this.props.tour.next()
            }
        }
    }


    handleSubmit(e) {
        e.preventDefault();
        if (this.loading) return;
        this.loading = true;

        GoaApi.Address.add(this.state, success => {
            this.props.onAdd(success.data.package);
        }, failure => {
            toastr.error(failure.message);
            this.loading = false;
        })
    }

    handleAutofill(e) {
        let newState = { ...this.state, ...e }
        this.setState(newState)
    }

    render() {
        return (
            <form onSubmit={this.handleSubmit}>
                <div>
                    <Header title='Details' top={true} />
                    <Spacer space='20px' />

                    <div style={STYLES.flexRow}>
                        <Text
                            autoFocus={true}
                            title='Sender Name'
                            onChange={e => this.setState({ name: e.target.value })}
                            value={this.state.name}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />

                        <Spacer space='10px' />

                        <Text
                            autoFocus={true}
                            title='Sender Company'
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

                    <div style={STYLES.flexRow}>
                        <StreetAddress
                            title='Street 1'
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
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
                            autoFocus={true}
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
                            stylescontainer={STYLES.input}
                            stylesselect={STYLES.selectInput}
                            styleslabel={STYLES.label}
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

                    <div style={STYLES.flexRow}>
                        <Button
                            props={{ type: 'submit' }}
                            color={GoaBrand.getPrimaryColor()}
                            stylesbutton={STYLES.button}
                            stylesbuttonhover={STYLES.buttonHover}
                        >
                            Add Address
                        </Button>
                        <Spacer space='20px' />
                        <Button
                            props={{
                                type: 'button',
                                onClick: this.props.onCancel
                            }}
                            color={GoaBrand.getPrimaryColor()}
                            stylesbutton={STYLES.button}
                            stylesbuttonhover={STYLES.buttonHover}
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
            </form>
        )
    }
}

const STYLES = {
    flexRow: {
        display: 'flex',
        alignItems: 'center',
    },
    input: {
        flex: 1
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
        borderColor: '#96A0AF',
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
    }
}