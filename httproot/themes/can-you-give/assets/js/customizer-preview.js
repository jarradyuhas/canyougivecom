(function($) {
    'use strict';

    if (typeof wp === 'undefined' || typeof wp.customize === 'undefined') return;

    var preview = (typeof TMT_CUSTOMIZER_PREVIEW !== 'undefined') ? TMT_CUSTOMIZER_PREVIEW : {};
    var colorMap = preview.colorMap || {};
    var colorHexDefaults = preview.colorHexDefaults || {};
    var colorOklchDefaults = preview.colorOklchDefaults || {};

    function normalizeHex(hex) {
        hex = String(hex).replace('#', '').toLowerCase();
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        return '#' + hex;
    }

    function hexToOklch(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        var r = parseInt(hex.substring(0, 2), 16) / 255;
        var g = parseInt(hex.substring(2, 4), 16) / 255;
        var b = parseInt(hex.substring(4, 6), 16) / 255;

        var l = 0.2126 * r + 0.7152 * g + 0.0722 * b;
        var max = Math.max(r, g, b);
        var min = Math.min(r, g, b);
        var chroma = max - min;
        var hue = 0;

        if (chroma > 0) {
            if (max === r) hue = ((g - b) / chroma) % 6;
            else if (max === g) hue = (b - r) / chroma + 2;
            else hue = (r - g) / chroma + 4;
            hue = hue * 60;
            if (hue < 0) hue += 360;
        }

        return 'oklch(' + l.toFixed(3) + ' ' + (chroma * 0.4).toFixed(3) + ' ' + hue.toFixed(1) + ')';
    }

    function resolveColor(settingId, value) {
        if (!value) return value;
        if (value.indexOf('oklch') === 0) return value;

        var defaultHex = colorHexDefaults[settingId];
        if (defaultHex && normalizeHex(value) === normalizeHex(defaultHex)) {
            return colorOklchDefaults[settingId] || hexToOklch(value);
        }

        return hexToOklch(value);
    }

    function applyColor(property, settingId, value) {
        if (property && value) {
            var oklch = resolveColor(settingId, value);
            document.documentElement.style.setProperty(property, oklch);
            var root = document.getElementById('tmt-app');
            if (root) root.style.setProperty(property, oklch);
        }
    }

    $(function() {
        Object.keys(colorMap).forEach(function(settingId) {
            var cssProperty = colorMap[settingId];
            wp.customize(settingId, function(setting) {
                applyColor(cssProperty, settingId, setting.get());
                setting.bind(function(newValue) {
                    applyColor(cssProperty, settingId, newValue);
                });
            });
        });
    });

}(jQuery));
