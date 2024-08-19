import React from 'react';
import GoaApi from '../../../common/api';
import Text from '../../../common/inputs/text';
import Button from '../../../common/inputs/button';
import toastr from 'toastr';
import Functions from '../../../common/functions';
import GoaBrand from '../../../common/brand';

export default class Register extends React.Component {
    constructor(props) {
        super(props)
        this.state = {
            name: '',
            company: '',
            email: '',
            password: '',
            passwordConfirm: '',
            phone: ''
        }

        this.handleRegister = this.handleRegister.bind(this);
    }

    handleRegister(e) {
        e.preventDefault();
        if (this.state.password != this.state.passwordConfirm) {
            toastr.warning('Passwords do not match');
            return;
        }

        let queryParams = Functions.getQueryParams();
        let args = { ...this.state, code: queryParams.code, user_id: queryParams.user_id }

        GoaApi.Authenticate.register(args, success => {
            this.props.history.push('/thank-you');
        }, failure => {
            toastr.error(failure.message);
        })
    }

    render() {

        let queryParams = Functions.getQueryParams();
        let message = null;
        if (queryParams.store) {
            message = (<p style={STYLES.message}>Thanks for installing our {queryParams.store} app.  Our system requires email and phone number. Please register below to start printing labels.</p>)
        }

        return (
            <div>
            
            <form onSubmit={this.handleRegister}>
                {message}
                <div style={STYLES.title}>Register Here</div>
                <Text
                    styleslabel={STYLES.inputLabel}
                    stylesinput={STYLES.inputText}
                    title='Name'
                    autoComplete='name'
                    onChange={e => this.setState({ name: e.target.value })}
                    value={this.state.name}
                />
                <Text
                    styleslabel={STYLES.inputLabel}
                    stylesinput={STYLES.inputText}

                    title='Company'
                    autoComplete='company'
                    onChange={e => this.setState({ company: e.target.value })}
                    value={this.state.company}
                />
                <Text
                    styleslabel={STYLES.inputLabel}
                    stylesinput={STYLES.inputText}
                    title='Email'
                    autoComplete='email'
                    onChange={e => this.setState({ email: e.target.value })}
                    value={this.state.email}
                />
                <Text
                    styleslabel={STYLES.inputLabel}
                    stylesinput={STYLES.inputText}
                    title='Phone'
                    autoComplete='phone'
                    onChange={e => this.setState({ phone: e.target.value })}
                    value={this.state.phone}
                />
                <Text
                    styleslabel={STYLES.inputLabel}
                    stylesinput={STYLES.inputText}
                    title='Password'
                    autoComplete='password'
                    type='password'
                    onChange={e => this.setState({ password: e.target.value })}
                    value={this.state.password}
                />
                <Text
                    styleslabel={STYLES.inputLabel}
                    stylesinput={STYLES.inputText}
                    title='Confirm Password'
                    autoComplete='confirm-password'
                    type='password'
                    onChange={e => this.setState({ passwordConfirm: e.target.value })}
                    value={this.state.passwordConfirm}
                />
                <Button
                    stylesbuttonhover={STYLES.buttonHover}
                    stylesbutton={STYLES.button}
                    props={{ type: 'submit' }}
                    color='white'
                >
                    Register
                </Button>
               
                
            </form>
            </div>
        )
    }
}

const STYLES = {
    title: {
        fontFamily: 'poppins',
        fontSize: '34px',
        lineHeight: '101px',
        color: '#273240',
        fontWeight: '600',
    },
    inputLabel: {
        fontSize: '18px',
        fontWeight: '600',
        color: 'black',
        fontFamily: 'poppins'
    },
    inputText: {
        fontSize: '18px',
        borderColor: GoaBrand.getActiveColor(),
        borderRadius: '15px',
        height: '40px',
        fontWeight: '600',
        backgroundColor: 'white',
        fontFamily: 'poppins',


    },
    buttonHover: {
        backgroundColor: GoaBrand.getPrimaryHoverColor(),
        color: 'white'
    },
    button: {
        backgroundColor: GoaBrand.getPrimaryColor(),
        borderColor: '#04009a',
        borderRadius: '15px',
        height: '40px',
        fontSize: '18px',
        fontWeight: '600',
        marginBottom: '20px',
        fontFamily: 'poppins'


    },
    message: {
        fontSize: '20px',
        color: 'white',
        fontFamily: 'poppins'
    },
    back: {
        fontSize: '12px',
        color: 'grey'
    }
}