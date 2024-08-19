import React from 'react';
import {NavLink} from "react-router-dom";
import GoaBrand from '../brand';
import {TourMethods} from 'react-shepherd'

const MenuItem = (props) => {
    let containerStyles = {...STYLES.container, ...(props.containerStyles ? props.containerStyles : {})};
    let iconContainerStyles = {...STYLES.iconContainer, ...(props.itemStyles ? props.itemStyles : {})};

    // get link component and link props based on if menu has method or is an actual route
    let linkProps = props.menu.method ? {onClick: props.menu.method} : {to: props.prefix + props.menu.link, activeStyle:STYLES.iconHover, exact: true}
    let linkTitleProps = props.menu.method ? {onClick: props.menu.method} : {to: props.prefix + props.menu.link}
    let LinkComponent = (props.menu.method) ? 'div' : NavLink;
    return (
        <TourMethods>
            {(tourContext) => (
                <div style={containerStyles}>
                    <LinkComponent
                        onClick={() => {
                            if(tourContext.isActive()){
                                tourContext.next()
                            }
                        }} 
                        {...linkProps} 
                        style={iconContainerStyles} 
                        className={props.className}
                    >
                        <i className={props.menu.icon}/>
                    </LinkComponent>
                    {props.menu.title ? <LinkComponent style={STYLES.title} {...linkTitleProps}>{props.menu.title}</LinkComponent> : null}
                </div>
            )}
        </TourMethods>
    )
}


const STYLES = {
    container:  {
        cursor: 'pointer',
        fontSize: '12px',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        marginBottom: '20px'
    },
    iconContainer: {
        textDecoration: 'none',
        width: '50px',
        height: '50px',
        border:  '2px solid #f1f4f9',
        borderRadius: '10px',
        marginBottom: '5px',
        color: '#f1f4f9',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontSize: '30px'
    },
    iconHover: {
        backgroundColor: GoaBrand.getPrimaryColor(),
        color: 'white'
    },
    title: {
        textDecoration: 'none',
        color: '#555'
    }
}

export default MenuItem;