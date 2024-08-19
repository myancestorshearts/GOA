import React from 'react'

import GoaBrand from '../brand';

const Header = (props) => {

    let headerStyles = {
        ...STYLES.header,
        ...(props.top ? STYLES.topHeader : {}),
        ...(props.style ? props.style : {})
    }

    return (
        <div style={headerStyles}>
            {props.title}
        </div>
    )
}

export default Header;

const STYLES = {
    topHeader: {
        borderRadius: '20px 20px 0px 0px',
        marginTop: '-20px',
        
        

    },
    header: {
        fontFamily: 'poppins',
        fontWeight: '600',
        fontSize: '18px',
        backgroundColor: GoaBrand.getBackgroundLightColor(),
        color: '#273240',
        overflowWrap: 'break-word',
        height: '50px',
        display: 'flex',
        alignItems: 'center',
        marginLeft: '-20px',
        marginRight: '-20px',
        paddingLeft: '20px',
        paddingRight: '20px',
        
    }
}
