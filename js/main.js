/*
 * jQuery File Upload Plugin JS Example 6.7
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global $, window, document */
var $activeImage;
var cropCoordinates={};
$(function () {
    'use strict';

    // Initialize the jQuery File Upload widget:
    $('#fileupload').fileupload();

    // Enable iframe cross-domain access via redirect option:
    $('#fileupload').fileupload(
        'option',
        'redirect',
        window.location.href.replace(
            /\/[^\/]*$/,
            '/cors/result.html?%s'
        )
    );

   /* if (window.location.hostname === 'blueimp.github.com') {
        // Demo settings:
        $('#fileupload').fileupload('option', {
            url: '//jquery-file-upload.appspot.com/',
            maxFileSize: 5000000,
            acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
            process: [
                {
                    action: 'load',
                    fileTypes: /^image\/(gif|jpeg|png)$/,
                    maxFileSize: 20000000 // 20MB
                },
                {
                    action: 'resize',
                    maxWidth: 1440,
                    maxHeight: 900
                },
                {
                    action: 'save'
                }
            ]
        });
        // Upload server status check for browsers with CORS support:
        if ($.support.cors) {
            $.ajax({
                url: '//jquery-file-upload.appspot.com/',
                type: 'HEAD'
            }).fail(function () {
                $('<span class="alert alert-error"/>')
                    .text('Upload server currently unavailable - ' +
                            new Date())
                    .appendTo('#fileupload');
            });
        }
    } else {//*/
        // Load existing files:
        loadExistingFiles();
    /*} //*/

    /***************** added by Agustín Amenabar *******************************/
     $('#modal-gallery').on('displayed', function () {
        var modalData = $(this).data('modal');
        // modalData.$links is the list of (filtered) element nodes as jQuery object
        // modalData.img is the img (or canvas) element for the loaded image
        // modalData.options.index is the index of the current link
        $('#urlImage').css('vertical-align','top');
        $activeImage = $(modalData.img);
        $('#urlImage').val($activeImage.attr('src'));
        
        $('#inWidthCrop').val($('#croppingModal').attr('data-width'));
        $('#inHeightCrop').val($('#croppingModal').attr('data-height'));
        
        $('#startCrop').click(function(eve){
            eve.preventDefault();
            var $cm = $('#croppingModal');
            var cssProperties = Array('margin-left','margin-top','width');
            for (var i = cssProperties.length - 1; i >= 0; i--) {
                $cm.css(cssProperties[i],$('#modal-gallery').css(cssProperties[i]));
            };
             $cm.find('.modal-body').css('max-height','none');
            $('#croppingModal').modal('show').find('.close, .closeModal').click(function(eve){
                eve.preventDefault();
                $('#croppingModal').modal('hide');
            });
            $('#modal-gallery').modal('hide');
            
            var picWidth = $activeImage.width();
            var picHeight = $activeImage.height();
            if (!picWidth) return;
            $('#canvasToCrop').attr('width',picWidth);
            $('#canvasToCrop').attr('height',picHeight);

            var canContext = $('#canvasToCrop')[0].getContext("2d");
            canContext.drawImage($activeImage[0],0,0,picWidth,picHeight);

            var jcOptions = {};
            if($('#inWidthCrop').val() && $('#inHeightCrop').val()){
                jcOptions.aspectRatio=$('#inWidthCrop').val() / $('#inHeightCrop').val();
                $('#croppingModal').find('h3 .dimentions').text('to '+$('#inWidthCrop').val() + ' x ' + $('#inHeightCrop').val() + ' px');
            }

            cropCoordinates.source = {
                width:picWidth,
                height:picHeight,
                endWidth:$('#inWidthCrop').val(),
                endHeight:$('#inHeightCrop').val(),
                file:$activeImage.attr('src')
            };
            jcOptions.onSelect = function(c){
                cropCoordinates.c=c;
            };

            $('#canvasToCrop').Jcrop(jcOptions);
            $('#btnDoCrop').click(function(eve){
                eve.preventDefault();
                $.post('server/php/image_crop_and_size.php',cropCoordinates,afterCropping)
            });
        });
        
    });
    $('a.modal-copy').zclip({
            path:'js/ZeroClipboard.swf',
            copy:function(){return $('#urlImage').val();}
        });
});

function afterCropping(data,textStatus,jqXHR){
    $('#croppingModal').modal('hide');
    $('tbody.files').find('tr').remove();
    loadExistingFiles();
}

function loadExistingFiles(){
    $('#fileupload').each(function () {
            var that = this;
            $.getJSON(this.action, function (result) {
                if (result && result.length) {
                    $(that).fileupload('option', 'done')
                        .call(that, null, {result: result});
                }
            });
        });
}

/*
 * Special event for image load events
 * Needed because some browsers does not trigger the event on cached images.

 * MIT License
 * Paul Irish     | @paul_irish | www.paulirish.com
 * Andree Hansson | @peolanha   | www.andreehansson.se
 * 2010.
 *
 * Usage:
 * $(images).bind('load', function (e) {
 *   // Do stuff on load
 * });
 * 
 * Note that you can bind the 'error' event on data uri images, this will trigger when
 * data uri images isn't supported.
 * 
 * Tested in:
 * FF 3+
 * IE 6-8
 * Chromium 5-6
 * Opera 9-10
 */
(function ($) {
    $.event.special.load = {
        add: function (hollaback) {
            if ( this.nodeType === 1 && this.tagName.toLowerCase() === 'img' && this.src !== '' ) {
                // Image is already complete, fire the hollaback (fixes browser issues were cached
                // images isn't triggering the load event)
                if ( this.complete || this.readyState === 4 ) {
                    hollaback.handler.apply(this);
                }

                // Check if data URI images is supported, fire 'error' event if not
                else if ( this.readyState === 'uninitialized' && this.src.indexOf('data:') === 0 ) {
                    $(this).trigger('error');
                }

                else {
                    $(this).bind('load', hollaback.handler);
                }
            }
        }
    };
}(jQuery));