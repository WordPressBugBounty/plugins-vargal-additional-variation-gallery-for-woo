jQuery(document).ready(function ($) {
    'use strict';
    if (typeof wbeParams === "undefined"){
        return;
    }
    $(document).on('bulky_gallery_attachment_render',function (e,src, id){
        if (/^(http(s?):)\/\/.*\.(?:mp4|webm)$/i.test(src)){
            return `<li class="vi-wbe-gallery-image vargal-thumb-video" data-id="${id}"><i class="vi-wbe-remove-image dashicons dashicons-no-alt"> </i><video preload="metadata" src="${src}"></video></li>`;
        }
        return false;
    });
    $(document).on('bulky_gallery_approve_attachment',function (e,cell, obj, attachment){
        let y = cell.getAttribute('data-y');
        if (!obj?.options?.data[y] ||  obj.options.data[y][3] !== 'variation'){
            return false;
        }
        let attachment_type = attachment?.type, attachment_subtype = attachment?.subtype;
        if (!attachment?.id){
            return false;
        }
        if (!['image', 'video'].includes(attachment_type)){
            $(document.body).trigger('villatheme_show_message', [vargal_params.invalid_type_mes, ['vargal-img-error','error'], '', false, 5500]);
            return false;
        }
        if (attachment_type === 'video' && !['mp4','webm'].includes(attachment_subtype)){
            $(document.body).trigger('villatheme_show_message', [vargal_params.invalid_video_mes, ['vargal-img-error','error'], '', false, 5500]);
            return false;
        }
        if ($('.vi-wbe-gallery-image[data-id="' + attachment.id+'"]' ).length){
            $(document.body).trigger('villatheme_show_message', [vargal_params.duplicate_img_mes, ['vargal-img-notice'], '', false, 1500]);
            return false;
        }
        return true;
    });
});