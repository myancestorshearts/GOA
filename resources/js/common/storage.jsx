
export default class Storage {
    
    static set(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    }

    static get(key) {
        let value = localStorage.getItem(key);
        return JSON.parse(value);
    }

    static has(key) {
        return localStorage.hasOwnProperty(key)
    }
    
    static remove(key) {
        localStorage.removeItem(key);
    }  
}