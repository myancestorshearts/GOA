import React from 'react';
import GoaApi from '../../../common/api';
import Text from '../../../common/inputs/text';
import Button from '../../../common/inputs/button';
import toastr from 'toastr';
import GoaBrand from '../../../common/brand';


export default class Forgot extends React.Component {
    constructor(props) {
        super(props)
        this.state = {
            email: ''
        }
        this.handleForgot = this.handleForgot.bind(this)
    }

    handleForgot(e) {
        e.preventDefault();
        GoaApi.Authenticate.passwordRequest(this.state, success => {
            toastr.success('Sent password reset email')
            this.props.history.push('/');
        }, failure => {
            toastr.error(failure.message);
        })
    }

    render() {
        return (
            <form onSubmit={this.handleForgot}>
                <Text
                    stylescontainer={STYLES.inputContainer}
                    styleslabel={STYLES.inputLabel}
                    stylesinput={STYLES.inputText}
                    title='Email'
                    autoComplete='email'
                    onChange={e => this.setState({ email: e.target.value })}
                    value={this.state.email}
                />
                <Button
                    stylesbuttonhover={STYLES.buttonHover}
                    stylesbutton={STYLES.button}
                    props={{ type: 'submit' }}
                    color='white'
                >
                    Send Password Reset Email
                </Button>
            </form>
        )
    }
}

const STYLES = {
    inputText: {
        fontSize: '18px',
        borderColor: GoaBrand.getActiveColor(),
        borderRadius: '15px',
        height: '40px',
        fontWeight: '600',
        backgroundColor: 'white',
        fontFamily: 'poppins',
    },
    inputLabel: {
        fontSize: '18px',
        fontWeight: '600',
        color: 'black',
        fontFamily: 'poppins',
    },
    buttonHover: {
        backgroundColor: GoaBrand.getPrimaryHoverColor(),
        color: 'white',
    },
    button: {
        backgroundColor: GoaBrand.getPrimaryColor(),
        borderRadius: '15px',
        height: '40px',
        fontSize: '16px',
        fontWeight: '600',
        marginBottom: '20px',
        fontFamily: 'poppins',

    },
}