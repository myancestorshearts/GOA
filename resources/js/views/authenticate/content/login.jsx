import React from 'react';
import GoaApi from '../../../common/api';
import Text from '../../../common/inputs/text';
import Button from '../../../common/inputs/button';
import toastr from 'toastr';
import { NavLink } from "react-router-dom";
import GoaBrand from '../../../common/brand';

export default class Login extends React.Component {
    constructor(props) {
        super(props)
        this.state = {
            email: '',
            password: ''
        }
        this.handleLogin = this.handleLogin.bind(this)
    }

    handleLogin(e) {
        e.preventDefault();
        GoaApi.Authenticate.login({ email: this.state.email, password: this.state.password }, success => {
            window.location = '/portal'
        }, failure => {
            toastr.error(failure.message);
        })
    }

    render() {
        return (
            <form onSubmit={this.handleLogin}>
                <div style={STYLES.title}>Welcome back</div>
                <div style={STYLES.subTitle}>Please put in your correct login credentials below to start using the app</div>


                <Text
                    stylescontainer={STYLES.inputContainer}
                    styleslabel={STYLES.inputLabel}
                    stylesinput={STYLES.inputText}
                    title='Email'
                    autoComplete='email'
                    onChange={e => this.setState({ email: e.target.value })}
                    value={this.state.email}
                />
                <Text
                    stylescontainer={STYLES.inputContainer}
                    styleslabel={STYLES.inputLabel}
                    stylesinput={STYLES.inputText}
                    title='Password'
                    autoComplete='password'
                    type='password'
                    onChange={e => this.setState({ password: e.target.value })}
                    value={this.state.password}
                />


                <NavLink to='forgot' style={STYLES.link}>Forgot Password?</NavLink>
                <Button
                    stylesbuttonhover={STYLES.buttonHover}
                    stylesbutton={STYLES.button}
                    type='submit'
                    color='white'
                >
                    Sign in
                </Button>
                <div style={STYLES.account}>Don't have an account? <a href="/register" style={STYLES.signup}>Sign up free</a></div>

            </form>
        )
    }
}

const STYLES = {
    inputLabel: {
        fontSize: '18px',
        fontWeight: '600',
        color: 'black',
        fontFamily: 'poppins'
    },
    account: {
        fontWeight: '600',
        color: '#96A0AF',
        marginTop: '10px',
        textAlign: 'center',
        fontFamily: 'poppins'

    },
    signup: {
        marginTop: '10px',
        color: 'black',
        textAlign: 'center',
        textDecoration: 'wavy underline',
        fontWeight: '600',
        marginTop: '25px',
        textUnderlineOffset: '.25em',
        fontFamily: 'poppins'

    },
    buttonHover: {
        backgroundColor: GoaBrand.getPrimaryHoverColor(),
        color: 'white'
    },
    button: {
        backgroundColor: GoaBrand.getPrimaryColor(),
        borderRadius: '15px',
        height: '40px',
        fontSize: '18px',
        fontWeight: '600',
        marginBottom: '20px',
        fontFamily: 'poppins'

    },
    inputContainer: {

    },
    inputText: {
        fontSize: '18px',
        borderColor: GoaBrand.getActiveColor(),
        borderRadius: '15px',
        height: '40px',
        fontWeight: '600',
        backgroundColor: 'white',
        fontFamily: 'poppins'
    },
    title: {
        fontFamily: 'poppins',
        fontSize: '34px',
        lineHeight: '51px',
        color: '#273240',
        fontWeight: '600'
    },
    link: {
        color: 'rgb(170, 170, 170)',
    },
    subTitle: {
        color: '#273240',
        fontFamily: 'poppins',
        fontSize: '18px',
        lineHeight: '27px',
        fontWeight: '500',
        marginTop: '10px',
        marginBottom: '40px'
    }
}
