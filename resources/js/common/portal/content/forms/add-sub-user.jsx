
import React from 'react';

import Button from '../../../inputs/button';
import Spacer from '../../../components/spacer';
import GoaBrand from '../../../brand';
import InputText from '../../../inputs/text';
import Header from '../../../fields/header';

import toastr from 'toastr';


export default class AddApiCallback extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            name: '',
            email: ''
        };

        this.handleSubmit = this.handleSubmit.bind(this);
    }

    handleSubmit(e) {
        e.preventDefault();
        GoaApi.SubUser.add(this.state, success => {
            this.props.onAdd(success.data.model);
        }, failure => {
            toastr.error(failure.message);
        })
    }

    render() {

        return (
            <form onSubmit={this.handleSubmit}>
                <Header title='Add Sub User' top={true} />
                <Spacer space='20px' />
                <InputText
                    autoFocus={true}
                    title='Name'
                    value={this.state.name}
                    onChange={e => this.setState({ name: e.target.value })}
                    stylesinput={STYLES.textInput}
                    styleslabel={STYLES.label}
                />
                <InputText
                    title='Email'
                    value={this.state.email}
                    onChange={e => this.setState({ email: e.target.value })}
                    stylesinput={STYLES.textInput}
                    styleslabel={STYLES.label}
                />
                <Button
                    props={{ type: 'submit' }}
                    color={GoaBrand.getPrimaryColor()}
                    stylesbutton={STYLES.button}
                    stylesbuttonhover={STYLES.buttonHover}
                >
                    Add Sub User
                </Button>
            </form>
        )
    }
}

const STYLES = {
    confirmButtonsContainer: {
        display: 'flex',
        minWidth: '330px'
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