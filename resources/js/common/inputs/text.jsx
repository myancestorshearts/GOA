
import React from 'react';
import Functions from '../functions';

const Text = React.forwardRef((props, ref) => {

    let id = Functions.getRandomId();
    let inputStyles = { ...STYLES.input };
    let labelStyles = { ...STYLES.label };
    let containerStyles = { ...STYLES.container }

    let color = props.color ? props.color : 'black';
    if (props.color) {
        inputStyles.borderColor = color;
        inputStyles.color = color;
        labelStyles.color = color;
    }

    inputStyles = { ...inputStyles, ...(props.stylesinput ? props.stylesinput : {}) };
    labelStyles = { ...labelStyles, ...(props.styleslabel ? props.styleslabel : {}) };
    containerStyles = { ...containerStyles, ...(props.stylescontainer ? props.stylescontainer : {}) };

    return (
        <div style={containerStyles}>
            {props.title && <label htmlFor={id} style={labelStyles}>{props.title}</label>}
            <input
                id={id}
                ref={ref}
                type='text'
                style={inputStyles}
                {...props}
            />
        </div>
    )
});

export default Text;

const STYLES = {
    container: {
        display: 'flex',
        flexDirection: 'column',
        marginBottom: '10px'
    },
    input: {
        backgroundColor: 'transparent',
        height: '30px',
        borderRadius: '5px',
        border: '2px solid',
        paddingLeft: '10px',
        paddingRight: '10px',
        fontSize: '16px'
    },
    label: {
        marginBottom: '2px',
        fontSize: '16px',
        fontWeight: 'bold'
    }
}