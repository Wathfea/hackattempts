(function ($) {
    'use strict';

    $(document).on('click', '.disable_wp_login', function () {
        if ($('.disable_wp_login').prop('checked')) {
            $('#new_login_url').prop("disabled", false);
        } else {
            $('#new_login_url').attr('disabled', true);
        }
    });


    $(document).on('click', '.hackattempts_tab a', function () {
        $('.hackattempts_tab a').each(function () {
            $(this).removeClass('nav-tab-active');
        });

        $(this).addClass('nav-tab-active')
        $('.hc_section').hide();
        $('.hc_section').eq($(this).index()).show();
        return false;
    });

    $(document).on('click', '.firewall', function () {
        show_noty('This function is under construction!', 'error');
    });

    $(document).on('click', '.delete', function () {
        var ip = $(this).data('ip');
        var ajax_url = $('.ajax_url').val();

        $.post(ajax_url, {action: 'deleteFile', ip: ip}, function (data) {
            if (data.success) {
                show_noty('The file is deleted!', 'ok');
            } else {
                show_noty('Something went wrong, please try again!', 'error');
            }
        }, 'json');
    });

    $(document).on('click', '.add_block', function () {
        var ip = $(this).data('ip');
        var ajax_url = $('.ajax_url').val();

        $.post(ajax_url, {action: 'addBlock', ip: ip}, function (data) {
            if (data.success) {
                show_noty('The file is added to the blacklist!', 'ok');
            } else {
                show_noty('Something went wrong, please try again!', 'error');
            }
        }, 'json');
    });

    $(document).on('click', '.add_file', function () {
        var file = $('.protected_files').val();
        var ajax_url = $('.ajax_url').val();

        $.post(ajax_url, {action: 'addSecurity', file: file}, function (data) {
            if (data.success) {
                show_noty('The file is added to the security list!', 'ok');
            } else {
                show_noty('Something went wrong, please try again!', 'error');
            }
        }, 'json');
    });
    
    $(document).on('click', '.removeFile', function () {

        var file = $(this).data('remove');
        var ajax_url = $('.ajax_url').val();

        $.post(ajax_url, {action: 'removeSecurity', file: file}, function (data) {
            if (data.success) {
                show_noty('The file is removed from the security list', 'ok');
            } else {
                show_noty('Something went wrong, please try again!', 'error');
            }
        }, 'json');
    });

     $(document).on('click', '.add_watch_file', function () {
        var file = $('.watched_files').val();
        var ajax_url = $('.ajax_url').val();

        $.post(ajax_url, {action: 'addWatch', file: file}, function (data) {
            if (data.success) {
                show_noty('The file is added to the watched list!', 'ok');
            } else {
                show_noty('Something went wrong, please try again!', 'error');
            }
        }, 'json');
    });   
    
    $(document).on('click', '.removeWatch', function () {

        var file = $(this).data('remove');
        var ajax_url = $('.ajax_url').val();

        $.post(ajax_url, {action: 'removeWatch', file: file}, function (data) {
            if (data.success) {
                show_noty('The file is removed from the security list', 'ok');
            } else {
                show_noty('Something went wrong, please try again!', 'error');
            }
        }, 'json');
    });
    
    $(document).on('submit', '#hackattemptsForm', function (e) {
        e.preventDefault();

        var ajaxurl = $(".ajax_url").val();
        var formData = new FormData(this);

        formData.append("action", "saveSettings");

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            mimeType: "multipart/form-data",
            async: false,
            cache: false,
            contentType: false,
            processData: false,
            success: function () {
                location.reload();
            }
        });
    });

    function show_noty(text, state) {
        var notify_class;

        if (state == 'ok') {
            notify_class = 'notify_ok';
        } else {
            notify_class = 'notify_err';
        }

        $('.notify').addClass(notify_class).text(text).show();
        $('.notify').delay(5000).slideUp('slow');
        $('.notify').html();
    }
    ;

})(jQuery);
