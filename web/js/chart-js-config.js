/*!
 * spur-template - An admin template based on Bootstrap 4
 * Version v1.1.0
 * Copyright 2016 - 2019 Alexander Rechsteiner
 * https://hackerthemes.com
 */

// Keep pages usable if the Chart.js CDN is unavailable.
if (window.Chart && window.Chart.defaults && window.Chart.defaults.global) {
    window.Chart.defaults.global.defaultFontFamily = "'Open Sans', 'sans-serif'";
}

window.chartColors = {
    primary: '#3F84FC',
    secondary: '#727F94',
    success: '#1DAB47',
    info: '#8A3FFC',
    warning: '#FCAE3F',
    superwarning: '#fd7e14',
    danger: '#FC413F',
};
