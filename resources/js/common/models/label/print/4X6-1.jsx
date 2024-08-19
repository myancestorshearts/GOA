
import React from 'react';

export default class Print4X6D1 extends React.Component {


    render() {

        let dpi = this.props.width / 4;

        let containerStyles = {
            width: (dpi * 4) + 'px'
        }

        let imageStyles = {
            width: (dpi * 4 - 20) + 'px',
            height: (dpi * 6 - 20) + 'px',
            ...STYLES.image
        }

        let images = this.props.images.map((x, i) => <img key={i} src={x} style={imageStyles} />)

        return (
            <div style={containerStyles}>
                {images}
            </div>
        )
    }
}


const STYLES = {
    image: {
        margin: '10px'
    }
}