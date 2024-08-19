// load prototypes in so we can use date time functions
import InitializePrototypes from "../../common/prototypes";
InitializePrototypes();


// load gao api user and brand for global use
import GoaApi from '../../common/api.jsx';
import GoaUser from '../../common/goa-user';
import GoaBrand from '../../common/brand';
import Storage from '../../common/storage';

window.GoaApi = GoaApi;
window.GoaUser = new GoaUser();
window.GoaBrand = new GoaBrand();
document.documentElement.style.setProperty(`--primaryColor`, GoaBrand.getPrimaryColor());

// portal component
import Header from './header';
import Panel from './panel/index';

import React from 'react';
import { Router, Route } from "react-router-dom";

import ActiveView from './active-view';

import ContentProfile from './content/profile';

import { ShepherdTour, TourMethods } from "react-shepherd";
import steps from "./shepherd/steps";
import history from "./history";
import Start from "./shepherd/start"

import ActiveModal from './modal/active';

import LeftMenu from './left-menu/index';

//import "shepherd.js/dist/css/shepherd.css";



export default class Portal extends React.Component {

    constructor(props) {

        super(props)

        // creat state with user
        this.state = {
            user: undefined
        }

    }

    componentDidMount() {
        // load user to the state
        this.subscribeUser = window.GoaUser.subscribe(user => {
            this.setState({ user: user })
        })
        window.GoaUser.loadUser(() => {
            window.location = '/';
        })
    }

    handleLogoutOfSubUser() {
        Storage.remove('goa-loginasuser-tokens');
        window.location = '/admin';
    }


    componentWillUnmount() {

        // unsubscribe user loading when we unmount 
        window.GoaUser.unsubscribe(this.subscribeUser);

    }

    render() {

        // check user if not set yet return null
        if (!this.state.user) return null;

        // create routes
        let routes = this.props.menus.map((x, i) => {
            if (!x.component) return null;
            let Component = x.component;
            return (x.showMethod && !x.showMethod(this.state.user)) ? null : <Route
                key={i}
                exact path={this.props.prefix + x.link}
                render={props => <Component user={this.state.user} {...props} />}
            />
        })

        // include logout of user
        let showLogoutOfSubuser = Storage.has('goa-loginasuser-tokens');

        // return portal view
        return (
            <div style={STYLES.body}>
                {
                    showLogoutOfSubuser ?
                        <div
                            style={STYLES.logoutOfUserContainer}
                            onClick={this.handleLogoutOfSubUser}
                        >
                            <a style={STYLES.logoutOfUserText} href='#'>You are logged in as {this.state.user.name}! Logout</a>
                        </div> : null
                }
                <Router ref={e => this.router = e} history={history}>
                    <ShepherdTour steps={steps} tourOptions={tourOptions}>
                        <TourMethods>
                            {tourContext => <Start startTour={tourContext} firstTime={this.state.user.first_time_login} />}
                        </TourMethods>


                        <div style={STYLES.container}>

                            {/* Side Menu */}
                            <LeftMenu
                                prefix={this.props.prefix}
                                menus={this.props.menus}
                                user={this.state.user}
                            />

                            <div style={STYLES.contentContainer}>
                                <Header user={this.state.user} prefix={this.props.prefix} />

                                <div style={STYLES.dynamicContainer}>
                                    {/* content section */}
                                    <div style={STYLES.routeContainer}>

                                        {routes}
                                        <Route exact path={this.props.prefix + '/profile'}>
                                            <ContentProfile user={this.state.user} />
                                        </Route>
                                    </div>

                                    {/* active model display*/}
                                    <ActiveView stateKey='active-model' />
                                </div>
                            </div>

                            <ActiveModal stateKey='active-modal' />

                        </div>
                    </ShepherdTour>
                </Router>
            </div>
        )
    }
}

const tourOptions = {
    defaultStepOptions: {
        cancelIcon: {
            enabled: true,
        },
        classes: "shepherd-theme-custom",
    },
    useModalOverlay: true,
};

const STYLES = {
    body: {
        overflow: 'hidden',
        flex: '1',
        display: 'flex',
        height: '100%',
        flexDirection: 'column'
    },
    menuContainer: {
        width: '150px'
    },
    container: {
        flex: '1',
        overflow: 'hidden',
        minHeight: '0px',
        display: 'flex',
        fontFamily: 'poppins',
        background: GoaBrand.getBackgroundColor()
    },
    menuPanel: {
        borderRadius: '0px 20px 20px 0px',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center'
    },
    routeContainer: {
        borderRadius: '20px',
        flex: 1,
        paddingRight: '40px',
        marginLeft: '20px',
        overflow: 'overlay',
        maxHeight: '100%',
        paddingBottom: '20px',
        marginRight: '-21px',
        width: '1px'
    },
    logoutOfUserContainer: {
        width: '100%',
        background: GoaBrand.getPrimaryColor(),
        padding: '20px',
        textAlign: 'center'
    },
    logoutOfUserText: {
        color: 'white',
        fontSize: '20px'
    },
    contentContainer: {
        flex: '1'
    },
    dynamicContainer: {

        flex: '1',
        overflow: 'hidden',
        minHeight: '0px',
        display: 'flex',
        height: 'calc(100% - 94px)'
    }
}