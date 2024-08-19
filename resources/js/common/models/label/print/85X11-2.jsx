
import React from 'react';

export default class Print85X11D2 extends React.Component {

    constructor(props) {
        super(props);
    }

    render() {

        let dpi = this.props.width / 11;

        let containerStyles = {
            width: (dpi * 11) + 'px'
        }

        let imageStyles = {
            width: (dpi * 4) + 'px',
            height: (dpi * 6) + 'px',
            margin: (dpi * .75) + 'px'
        }

        let images = this.props.images.map((x, i) => <img key={i} src={x} style={imageStyles} />)

        return (
            <div style={containerStyles}>
                {images}
            </div>
        )
    }
}