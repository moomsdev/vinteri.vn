// create a mutation observer to look for added 'attachments' in the media uploader
let mediaGridObserver = new MutationObserver(function (mutations) {

    // look through all mutations that just occured
    for (let i = 0; i < mutations.length; i++) {

        // look through all added nodes of this mutation
        for (let j = 0; j < mutations[i].addedNodes.length; j++) {

            //get the applicable element
            let element = jQuery(mutations[i].addedNodes[j]);

            //execute only if we have a class
            if (element.attr('class')) {
                let elementClass = element.attr('class');

                // find all 'attachments'
                if (element.attr('class').indexOf('attachment') !== -1) {

                    //find attachment inner (which contains subtype info)
                    let attachmentPreview = element.children('.attachment-preview');
                    if (attachmentPreview.length !== 0) {

                        //only run for SVG elements
                        if (attachmentPreview.attr('class').indexOf('subtype-svg+xml') !== -1) {

                            let handler = function (element) {
                                jQuery.ajax({
                                    url: '/wp-admin/admin-ajax.php',
                                    type: "POST",
                                    dataType: 'html',
                                    data: {
                                        'action': 'mm_get_attachment_url_thumbnail',
                                        'attachmentID': element.attr('data-id')
                                    },
                                    success: function (data) {
                                        if (data) {
                                            element.find('img').attr('src', data);
                                            element.find('.filename').text('SVG Image');
                                        }
                                    }
                                });
                            }(element);
                        }
                    }
                }
            }
        }
    }
});

let attachmentPreviewObserver = new MutationObserver(function (mutations) {
    for (var i = 0; i < mutations.length; i++) {
        for (var j = 0; j < mutations[i].addedNodes.length; j++) {
            var element = $(mutations[i].addedNodes[j]);
            var onAttachmentPage = false;
            if ((element.hasClass('attachment-details')) || element.find('.attachment-details').length != 0) {
                onAttachmentPage = true;
            }

            if (onAttachmentPage == true) {
                var urlLabel = element.find('label[data-setting="url"]');
                if (urlLabel.length != 0) {
                    var value = urlLabel.find('input').val();
                    element.find('.details-image').attr('src', value);
                }
            }
        }
    }
});

jQuery(document).ready(function () {
    mediaGridObserver.observe(document.body, {
        childList: true,
        subtree: true
    });

    // attachmentPreviewObserver.observe(document.body, {
    //     childList: true,
    //     subtree  : true
    // });
});
