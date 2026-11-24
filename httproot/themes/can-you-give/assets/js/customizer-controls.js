(function($) {
    'use strict';

    if (typeof wp === 'undefined' || typeof wp.customize === 'undefined') return;

    var mediaPairs = (typeof TMT_CUSTOMIZER_CONTROLS !== 'undefined')
        ? TMT_CUSTOMIZER_CONTROLS.mediaPairs || {}
        : {};

    var mediaDefaults = (typeof TMT_CUSTOMIZER_CONTROLS !== 'undefined')
        ? TMT_CUSTOMIZER_CONTROLS.mediaDefaults || {}
        : {};

    Object.keys(mediaPairs).forEach(function(attachmentSettingId) {
        var urlSettingId = mediaPairs[attachmentSettingId];

        wp.customize(attachmentSettingId, function(attachmentSetting) {
            attachmentSetting.bind(function(attachmentId) {
                if (!attachmentId || attachmentId === 0) {
                    wp.customize(urlSettingId, function(urlSetting) {
                        urlSetting.set(mediaDefaults[urlSettingId] || '');
                    });
                    return;
                }
                var attachment = wp.media.attachment(attachmentId);
                attachment.fetch().done(function() {
                    var url = attachment.get('url') || '';
                    wp.customize(urlSettingId, function(urlSetting) {
                        urlSetting.set(url);
                    });
                });
            });
        });
    });

})(jQuery);
