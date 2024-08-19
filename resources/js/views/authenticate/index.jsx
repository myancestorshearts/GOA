import React from 'react';
import ReactDOM from 'react-dom'

import GoaBrand from '../../common/brand';

import Register from './content/register';
import Login from './content/login';
import Forgot from './content/forgot';
import Set from './content/set';
import Verify from './content/verify';
import ThankYou from './content/thank-you';


import ComponentFlexContainer from '../../common/components/flex-container';
import ComponentResponsive from '../../common/components/responsive';
import { BrowserRouter as Router, Switch, Route, NavLink } from "react-router-dom";

export default class LoginView extends React.Component {

    render() {

        return (
            <Router ref={e => this.router = e}>
                <ComponentFlexContainer gap='0px'>
                    {/* Authenticat Content */}
                    <div style={STYLES.containerAuthenticate}>
                        {/*Logo*/}
                        <img style={STYLES.logo} src={GoaBrand.getLogoUrl()} />

                        {/*Content Container*/}
                        <div style={STYLES.contentContainer}>
                            <div style={STYLES.innerContainer}>
                                <Switch>
                                    <Route path='/' exact component={Login} />
                                    <Route path='/register' component={Register} />
                                    <Route path='/forgot' component={Forgot} />
                                    <Route path='/set' component={Set} />
                                    <Route path='/verify' component={Verify} />
                                    <Route path='/thank-you' component={ThankYou} />
                                </Switch>
                            </div>
                        </div>
                    </div>

                    {/* Image */}
                    <ComponentResponsive min={900}>
                        <div style={STYLES.containerImage}>
                        </div>
                    </ComponentResponsive>
                </ComponentFlexContainer>

            </Router >
        )
    }
}

const STYLES = {
    containerImage: {
        backgroundImage: 'url("/global/assets/images/views/login/background.jpg")',
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        flex: 1
    },
    containerAuthenticate: {
        flex: 1,
        backgroundColor: GoaBrand.getBackgroundColor()
    },
    logo: {
        height: '100px',
        marginLeft: '35px',
        marginTop: '10px',
        lineHeight: '50px'
    },
    contentContainer: {
        width: '100%',
        height: '100%',
        marginTop: '-113px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center'
    },
    innerContainer: {
        maxWidth: '560px',
        padding: '20px',
        marginTop: '100px',
        marginBottom: '100px',
        width: '100%'
    },
}



ReactDOM.render(<LoginView />, document.getElementById('goa'));