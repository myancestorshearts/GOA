import React from 'react';


import MenuItem from './menu-item';

import GoaBrand from '../../brand';

const LeftMenu = (props) => {
    // create menus
    let menus = props.menus.map((x, i) => {
        if (x.viewOnly) return null;
        return (x.showMethod && !x.showMethod(props.user)) ? null : <MenuItem
            className={x.className}
            key={i}
            menu={x}
            prefix={props.prefix}
        />
    });

    return (
        <div style={STYLES.container}>
            <img style={STYLES.logo} src={GoaBrand.getLogoUrl()} />
            <div style={STYLES.mainMenu}>MAIN MENU</div>
            <div style={STYLES.menusContainer}>{menus}</div>
            <div style={STYLES.copyright}>&copy;{(new Date()).getFullYear()} All Rights Reserved</div>
        </div>
    )
}

export default LeftMenu;


const STYLES = {
    container: {
        display: 'flex',
        flexDirection: 'column',
        minHeight: '0px',
        minWidth: '225px',
        backgroundColor: GoaBrand.getBackgroundLightColor(),
        paddingLeft: '30px',
        paddingRight: '30px',
        paddingTop: '20px',
        boxShadow: '-1px 0px 29px rgba(180, 204, 222, 0.13)'
    },
    logo: {
        maxHeight: '120px',
        maxWidth: '225px',
    },
    mainMenu: {
        fontFamily: 'poppins',
        fontWeight: '600',
        fontSize: '16px',
        lineHeight: '24px',
        color: '#96A0AF',
        marginBottom: '20px',
        marginTop: '20px',
        paddingLeft: '15px'
    },
    copyright: {
        padding: '30px',
        fontFamily: 'poppins',
        fontWeight: '400',
        fontSize: '14px',
        color: '#96A0AF',
    },
    menusContainer: {
        flex: 1,
        overflow: 'auto',
        marginRight: '-30px',
        paddingRight: '30px'
    }
}