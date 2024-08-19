
import React from 'react';

const Label = (props) => {

    let labelStyles = STYLES.label;
    let valueStyles = STYLES.value;
    labelStyles = { ...labelStyles, ...(props.styleslabel ? props.styleslabel : {}) };
    valueStyles = { ...valueStyles, ...(props.stylesvalue ? props.stylesvalue : {}) };

    return (

        <React.Fragment>
            {
                props.label ?
                    <div style={labelStyles}>
                        {props.onDelete ? <i className='fa fa-close' style={STYLES.delete} onClick={props.onDelete} /> : null}
                        {props.label}
                    </div> : null
            }
            <div style={valueStyles}>
                {props.children}
            </div>
        </React.Fragment>
    )
}

export default Label;

const STYLES = {
    delete: {
        paddingRight: '5px',
        cursor: 'pointer'
    },
    label: {
        fontFamily: 'poppins',
        fontWeight: 'bold',
        fontSize: '12px',
        color: 'rgb(175, 175, 175)',
        marginBottom: '2px'
    },
    value: {
        fontFamily: 'poppins',
        fontWeight: 'bold',
        fontSize: '16px',
        color: '#555',
        marginBottom: '5px',
        wordBreak: 'break-all'
    }
}