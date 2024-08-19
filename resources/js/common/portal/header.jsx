import React from 'react';
import Button from '../inputs/button';
import CommonFunctions from '../functions';
import MenuItem from './menu-item';
import Modal from './modal';
import GoaBrand from '../brand';
import { CONFIRM } from './modal';
import FormAddFunds from '../../views/portal/content/forms/add-funds';
import { TourMethods } from 'react-shepherd'
import GoaState from '../goa-state';
import Functions from '../functions';
import InputText from '../inputs/text';

const MENUS = [
    {
        icon: 'fa fa-user',
        link: '/profile',
        id: 'profile',
    },
    {
        icon: 'fa fa-sign-out',
        method: () => {
            CONFIRM('Are you sure you want to logout?', () => {
                GoaApi.Authenticate.logout();
                window.location = '/';
            });
        }
    }
]

export default class Header extends React.Component {

    constructor(props) {

        super(props)
        this.state = {
            showAddFunds: false,
            search: ''
        }
        this.handleCancel = this.handleCancel.bind(this);
        this.handleAddedFunds = this.handleAddedFunds.bind(this);
        this.handleSelect = this.handleSelect.bind(this);

    }


    componentDidMount() {
        if (!Functions.isEmpty(GoaUser.user.parent_user_id)) {
            GoaApi.User.walletBalance({}, success => {
                GoaUser.updateUser({ wallet_balance: success.data.balance });
            })
        }
        GoaState.subscribe('GLOBAL_SEARCH', (x => this.setState({ search: x.query })))
    }


    handleCancel(tour) {
        this.setState({
            showAddFunds: false
        }, success => {
            if (tour.isActive()) {
                tour.next()
            }
        })
    }

    handleAddedFunds(tour) {
        this.setState({ showAddFunds: false }, success => {
            if (tour.isActive()) {
                tour.next()
            }
        });
    }

    handleSelect(tour) {
        if (Functions.isEmpty(GoaUser.user.parent_user_id)) {
            this.setState({ showAddFunds: true }, success => {
                if (tour.isActive()) {
                    tour.next()
                }
            })
        }
    }


    render() {

        let menus = MENUS.map((x, i) => {
            return (
                <MenuItem
                    key={i}
                    menu={x}
                    className={x.id}
                    containerStyles={STYLES.menuItem}
                    itemStyles={STYLES.icon}
                    prefix={this.props.prefix}

                />
            )
        });

        let balance = (this.props.user) ? CommonFunctions.convertToMoney(this.props.user.wallet_balance) : null

        return (
            <TourMethods>
                {(tourContext) => (
                    <div style={STYLES.container}>
                        {/* User Menu */}
                        <div style={STYLES.buttonsContainer}>

                            <InputText
                                value={this.state.search}
                                placeholder='Search....'
                                onChange={x => GoaState.set('GLOBAL_SEARCH', { query: x.target.value })}
                                stylesinput={STYLES.inputText}
                                stylescontainer={STYLES.inputContainer}
                            />
                            {/* Balance Button */}
                            <Button
                                stylesbutton={STYLES.balanceButton}
                                stylesbuttonhover={STYLES.balanceButtonHover}
                                color={GoaBrand.getPrimaryColor()}
                                invert={true}
                                props={{ onClick: () => { this.handleSelect(tourContext) } }}
                                className={'wallet'}
                            >
                                <div style={STYLES.balanceContainer}>
                                    <div style={STYLES.iconContainer}>
                                        <i className="fa fa-usd" style={{ fontSize: '18px', color: '#white' }} />
                                    </div>
                                    Balance: {balance}
                                </div>
                            </Button>

                            {/* Additional User Menus */}
                            {menus}

                        </div>

                        {
                            this.state.showAddFunds ?
                                <Modal className={'walletAdd'}>
                                    <FormAddFunds
                                        onAddedFunds={() => this.handleAddedFunds(tourContext)}
                                        onCancel={() => this.handleCancel(tourContext)}
                                    />
                                </Modal> : null
                        }
                    </div>
                )}
            </TourMethods>
        )
    }
}

const STYLES = {
    menuItem: {
        marginBottom: '0px'
    },
    menuPanel: {
        borderRadius: '0px 20px 20px 0px',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center'
    },
    menuContainer: {
        width: '150px'
    },
    buttonsContainer: {
        display: 'flex',
        flexDirection: 'row',
        minWidth: '100%',
        gap: '20px',
        flexWrap: 'wrap'
    },
    container: {
        padding: '20px',
        display: 'flex',
        flexDirection: 'row',
        alignItems: 'center',
        paddingRight: '20px',
    },
    balanceButton: {
        borderRadius: '10px',
        marginTop: '0px',
        height: '50px',
        width: '175px',
        fontSize: '12px',
        fontWeight: '600',
        lineHeight: '1.5',
        padding: '20px',
        display: 'flex',
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        boxShadow: '2px 2px 5px ' + GoaBrand.getPrimaryColor()
    },
    balanceButtonHover: {
        backgroundColor: GoaBrand.getPrimaryColor(),
        color: 'white',
        borderColor: GoaBrand.getPrimaryColor()
    },
    iconContainer: {
        borderRadius: '20px',
        marginRight: '10px',
        paddingRight: '5px'
    },
    icon: {
        backgroundColor: 'white',
        color: '#cccccc',
        marginBottom: '0px',
        boxShadow: '2px 2px 5px #f1f4f9'
    },
    balanceContainer: {
        display: 'flex',
        flexDirection: 'row',
        alignItems: 'center'
    },
    inputText: {
        height: '50px',
        borderRadius: '10px',
        background: '#ffffff',
        boxShadow: 'rgb(241 244 249) 2px 2px 5px',
        border: '#ffffff',
        fontSize: '16px',
        fontFamily: 'poppins',
        paddingLeft: '15px'
    },
    inputContainer: {
        marginBottom: '0px',
        flex: 1
    },
    searchIcon: {
        position: 'absolute',
        left: '8.09%',
        right: '8.38%',
        top: '12.21%',
        bottom: '4.23%',
        background: '#7C88A1'
    }


}
