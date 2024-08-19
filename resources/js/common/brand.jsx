


export default class Brand {
    static primaryColor = window.GOA_APP_COLOR
    static backgroundColor = '#DDEEFE';
    static backgroundLightColor = '#EFF8FE';
    static activeColor = '#3B99F4';
    static logoUrl = window.GOA_APP_LOGO;
    static primaryHoverColor = '#0c0a6b';

    static getPrimaryColor() {
        return this.primaryColor;
    }

    static getBackgroundColor() {
        return this.backgroundColor;
    }

    static getBackgroundLightColor() {
        return this.backgroundLightColor;
    }

    static getActiveColor() {
        return this.activeColor;
    }

    static getLogoUrl() {
        return this.logoUrl;
    }

    static getPrimaryHoverColor() {
        return this.primaryHoverColor;
    }
}