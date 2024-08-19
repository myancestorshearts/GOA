
const Responsive = (props) => {

    if (props.min && window.innerWidth < props.min) return null;
    if (props.max && window.innerWidth > props.max) return null;

    return props.children;
}

export default Responsive;