import React from 'react';
import Button from '../../inputs/button';
import ColumnSelection from '../../inputs/column-select'

import StyledComponent from '../../components/styled-component';
import GoaBrand from '../../brand';

import FlexContainer from '../../components/flex-container';

//buttonText=Button Text
//buttonOnClick= On Click Function
//buttonStyles= Button Styles

const Panel = (props) => {
    return (
        <StyledComponent
            style={{ ...STYLES.container, ...(props.style ? props.style : {}) }}
            styleHover={props.styleHover ? props.styleHover : {}}
            tagName='div'
            className={props.className}
            props={{
                onClick: props.onClick
            }}
        >
            {
                props.headerTitle ?
                    <FlexContainer style={STYLES.headerContainer}>
                        {props.headerIcon && <i style={STYLES.headerIcon} className={props.headerIcon} />}
                        <span>{props.headerTitle}</span>

                        <div style={STYLES.buttonsContainer}>
                            {props.onDelete ?
                                <div style={STYLES.button}>
                                    <Button
                                        color={GoaBrand.getPrimaryColor()}
                                        stylesbutton={(props.buttonStyles) ? props.buttonStyles : { marginTop: '0px' }}
                                        props={{ onClick: props.onDelete }}
                                    >
                                        <i className={'fa fa-trash'} />
                                    </Button>
                                </div>
                                : null
                            }
                            {props.onExport ?
                                <div style={STYLES.button}>
                                    <Button
                                        color={GoaBrand.getPrimaryColor()}
                                        stylesbutton={(props.buttonStyles) ? props.buttonStyles : { marginTop: '0px' }}
                                        props={{ onClick: props.onExport }}
                                    >
                                        <i className={'fa fa-download'} />
                                    </Button>
                                </div>
                                : null
                            }
                            {props.onPrint ?
                                <div style={STYLES.button}>
                                    <Button
                                        color={GoaBrand.getPrimaryColor()}
                                        stylesbutton={(props.buttonStyles) ? props.buttonStyles : { marginTop: '0px' }}
                                        props={{ onClick: props.onPrint }}
                                    >
                                        <i className={'fa fa-print'} />
                                    </Button>
                                </div>
                                : null
                            }
                            {props.onAdd ?
                                <div style={STYLES.button}>
                                    <Button
                                        color={GoaBrand.getPrimaryColor()}
                                        stylesbutton={(props.buttonStyles) ? props.buttonStyles : { marginTop: '0px' }}
                                        props={{ onClick: props.onAdd }}
                                    >
                                        <i className={'fa fa-plus'} />
                                    </Button>
                                </div>
                                : null
                            }
                            {props.onRefreshSearch ?
                                <div style={STYLES.button}>
                                    <Button
                                        color={GoaBrand.getPrimaryColor()}
                                        stylesbutton={(props.buttonStyles) ? props.buttonStyles : { marginTop: '0px' }}
                                        props={{ onClick: props.onRefreshSearch }}
                                    >
                                        <i className={'fa fa-refresh'} />
                                    </Button>
                                </div>
                                : null
                            }
                            {props.onColumnSelect ?
                                <div style={STYLES.button}>
                                    <span>
                                        <ColumnSelection
                                            onUpdateTablePreferences={props.onColumnSelect}
                                            tablePreferences={props.tablePreferences}
                                            tableColumns={props.tableColumns}
                                        />
                                    </span>
                                </div>
                                : null
                            }
                            {props.buttonText || props.buttonClassName ?
                                <div style={STYLES.button}>
                                    <Button
                                        color={GoaBrand.getPrimaryColor()}
                                        stylesbutton={(props.buttonStyles) ? props.buttonStyles : { marginTop: '0px' }}
                                        props={{ onClick: props.buttonOnClick }}
                                    >
                                        {props.buttonText}
                                        <i className={props.buttonClassName} />
                                    </Button>
                                </div>
                                : null
                            }
                        </div>
                    </FlexContainer> : null
            }
            {props.children}
        </StyledComponent>
    )
}

export default Panel;
const STYLES = {
    container: {
        background: 'white',
        borderRadius: '20px',
        padding: '20px',
        boxShadow: '2px 2px 5px #e7e4e9'
    },
    headerContainer: {
        marginLeft: '-20px',
        marginRight: '-20px',
        paddingLeft: '20px',
        paddingRight: '20px',
        display: 'flex',
        fontSize: '25px',
        alignItems: 'center',
        paddingBottom: '20px',
        borderBottom: '2px solid #f1f4f9',
        marginBottom: '20px'
    },
    headerIcon: {
        marginRight: '10px',
        color: GoaBrand.getPrimaryColor()
    },
    button: {
        marginLeft: '10px'
    },
    buttonsContainer: {
        marginLeft: 'auto',
        display: 'flex'
    },
    selectContainer: {
        marginBottom: '0px',
        marginLeft: '20px'
    },
    select: {
        fontSize: '12px',
        height: '24px',
        minHeight: '20px'
    },
    searchContainer: {
        flex: 1,
        display: 'flex'
    },
    searchInput: {
        height: '35px',
        margin: '0px',
        border: '2px solid #f1f4f9',
        paddingLeft: '15px',
        fontSize: '18px',
        borderRadius: '20px',
        flex: 1,
        color: '#555'
    }
}
