// scroll to top on page load
window.onbeforeunload = function () {
    window.scrollTo(0, 0);
};
jQuery(document).ready(function ($) {

    var $submenu = $('#toplevel_page_wploti-settings .wp-submenu li');

    // init tabs

    $('#wploti_tabs').tabs({

        create: function(event, ui) {
            // Adjust hashes to not affect URL when clicked.
            var widget = $('#wploti_tabs').data("uiTabs");
            widget.panels.each(function(i){
              this.id = "uiTab_" + this.id; // Prepend a custom string to tab id.
              widget.anchors[i].hash = "#" + this.id;
              $(widget.tabs[i]).attr("aria-controls", this.id);
            });
          },
          activate: function(event, ui) {
            // Add the original "clean" tab id to the URL hash.
            window.location.hash = ui.newPanel.attr("id").replace("uiTab_", "");
            "option", "active"
          },

    }).show({ effect: "blind", duration: 800 });


    $(window).on("hashchange", function() {

        var $tab_hash = location.hash.split("#")[1];

        switch ($tab_hash) {
            case 'header':
                $active = 2
                break;

            case 'ip':
                $active = 3
                break;

            case 'keys':
                $active = 4
                break;

            case 'animation':
                $active = 5
                break;

            case 'message':
                $active = 6
                break;

            case 'extra':
                $active = 7
                break;

            default:
                $active = 1
                break;
                
        }

        $('#toplevel_page_wploti-settings .wp-submenu li.current').removeClass('current');
        $submenu[$active].classList.add('current');

    }).trigger("hashchange")


    

    $submenu.bind('click', function (e) {

        var $active ;
         
         $('#toplevel_page_wploti-settings .wp-submenu li.current').removeClass('current');
         $(this).addClass('current');

        $menu_hash = $(this).find('a').attr('href').split("#")[1];

        switch ($menu_hash) {
            case 'header':
                $active = 0
                break;

            case 'ip':
                $active = 1
                break;

            case 'keys':
                $active = 2
                break;

            case 'animation':
                $active = 3
                break;

            case 'message':
                $active = 4
                break;

            case 'extra':
                $active = 5
                break;

            default:
                break;
        }

        // Set active tab
        $("#wploti_tabs").tabs("option", "active", $active);

    });
    
      /*   $('.wploti_settings_page .animation-state').map(function(){
            console.log($(this)[0].src)
        }) */

        function wploti_state(){
            if($('#wploti-toggle-adminbar').hasClass('status-1')){ // on
                $('.wploti_animation_state').map(function(){
                    $('.wploti_animation_state > lottie-player').remove();
                    $('.wploti_animation_state').append(
                        $('<lottie-player/>')
                        .attr("autoplay", "true")
                        .attr("loop", "true")
                        .attr("src", wploti_var.IMG_path + "/green-on.json")
                        .addClass("animation-state")                           
                    )                  
                })
            }else{  //off
                $('.wploti_animation_state').map(function(){                
                    $('.wploti_animation_state > lottie-player').remove();
                    $('.wploti_animation_state').append(
                        $('<lottie-player/>')
                        .attr("autoplay", "true")
                        .attr("loop", "true")
                        .attr("src", wploti_var.IMG_path + "/red-off.json")
                        .addClass("animation-state")                        
                    )                 
                })
            };
        }

    /**
     * toggle wploti activation via menu bar ajax
   */

    $('#wploti-toggle-adminbar').on('click', function (e) {
        e.preventDefault();
        var security = $(this).data('security');
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_toggle_activation',
                security: security,
                payload: 'toggle_wploti_status',
            },
            type: 'post',
            success: function (result, textstatus) {

                /* console.log(result);
                console.log('sucess'); */
             
                $('#wploti-toggle-adminbar').toggleClass('status-1');
                wploti_state();
                $('#wploti_main_options').fadeToggle();
                $('.wploti-status input[type=radio]').prop('disabled', function (_, val) {
                    return !val;
                });
                $('#wploti-status').prop('checked', function (_, val) {
                    return !val;
                });
            },
            error: function (result) {
                /*console.log(result);

                console.log('fail');*/
            },
        })
    });

    /**
     * save header type 
     */ 

    $('input.wploti_header_type').on('change', function () {
        var header_type = $(this).val();
        var security = $(this).data('security');
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_header_type',
                security: security,
                header_type: header_type,
            },
            type: 'post',
            success: function (result, textstatus) {
                /* console.log(result);

                 console.log('sucess'); */

                 //window.opener.location.reload();

                $(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
            },
            error: function (result) {
                /*console.log(result);

                 console.log('fail');*/
            },
        })
    })

    /**
     * save whitelisted roles
     */ 

        $('input.wploti_whitelisted_roles').on('change', function () {
            var role = $(this).val();
            var security = $(this).data('security');
           
            if ($(this).is(':checked')) {
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'wploti_add_whitelisted_roles',
                        security: security,
                        role: role,
                    },
                    type: 'post',
                    success: function (result, textstatus) {
                        //console.log(result);
    
                        //console.log('sucess');
        
                        //window.opener.location.reload();
        
                        $(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
                    },
                    error: function (result) {
                        //console.log(result);
        
                        //console.log('fail');
                    },
                })
            } else {
                //console.log($(this).val() + ' is now unchecked');
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'wploti_remove_whitelisted_roles',
                        security: security,
                        role: role,
                    },
                    type: 'post',
                    success: function (result, textstatus) {
                        //console.log(result);
    
                        //console.log('sucess');
        
                        //window.opener.location.reload();
        
                        $(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
                    },
                    error: function (result) {
                        //console.log(result);
        
                        //console.log('fail');
                    },
                })
            }
        })
    
    
    /**
     * save input message value 
     */


    function get_tinymce_content(){
        if (jQuery("#wp-content-wrap").hasClass("tmce-active")){
            return tinyMCE.activeEditor.getContent();
        }else{
            return jQuery('#html_text_area_id').val();
        }
    }


    $('#wploti_text_message #wp-content-editor-tools').append(
        $('<button/>')
        .attr("type", "button")
        .attr("data-security", wploti_var.wploti_nonce)
        .attr("id", "wploti_message")
        .attr("name", "wploti_message")
        .text(wploti_translate.save_content)
        .addClass("button wploti_save")
    )   

    // init select2
    $('#wploti_whitelisted_users').select2({ 'placeholder': wploti_translate.wploti_whitelisted_users_placeholder });

    /**
     * add whitelisted users to databse on select
     */
    
    $('#wploti_whitelisted_users').on('select2:select', function (e) {

        var user_id = e.params.data.id;

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_add_whitelisted_users',
                user_id: user_id,
            },
            type: 'post',
            success: function (result, textstatus) {
                /* console.log(result);

                 console.log('sucess'); */

                $(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
            },
            error: function (result) {
                /*console.log(result);

                 console.log('fail');*/
            },
        })

    });

    /**
     * add whitelisted users to databse on select
     */
    
    $('#wploti_whitelisted_users').on('select2:unselect', function (e) {
        var user_id = e.params.data.id;
       
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_remove_whitelisted_users',
                user_id: user_id,
            },
            type: 'post',
            success: function (result, textstatus) {
                /*console.log(result);

                 console.log('sucess'); */

                $(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
            },
            error: function (result) {
                /*console.log(result);

                 console.log('fail');*/
            },
        })

    });
    
    $('button.wploti_save').on('click', function () {

        $(this).text(wploti_translate.saved_content);

        var message = get_tinymce_content();
        var security = $(this).data('security');

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_ajax_message',
                security: security,
                message: message,
            },
            type: 'post',
            success: function (result, textstatus) {
               /*  console.log(result);
                console.log('sucess'); */

                $(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
            },
            error: function (result) {
                /*console.log(result);
                console.log('fail');*/
            },
        })
        
        setTimeout(() => {
            $(this).text(wploti_translate.save_content)
           /*  wploti_var.refresh_active = false;
            console.log(wploti_var.refresh_active); */
        }, 5000);
    })
    
    /**
     *  (js) dismiss activation notice
     */

    $(document).on('click', '.wploti-activation-dismiss', function (event) {
        event.preventDefault();
        var security = $(this).data('security');
        $.post(ajaxurl, {
            action: 'wploti_ajax_dismiss_activation_notice',
            security: security,
        });
        $('#wploti_enabled_notice').fadeOut('3000');
    });

    /**
     *  (js) dismiss notes notice
     */

    $('#wploti_note_notice .wploti-note-dismiss').on('click', function (event) {
        event.preventDefault();
        var security = $(this).data('security');
        $.post(ajaxurl, {
            action: 'wploti_ajax_dismiss_notes_notice',
            security: security,
        });
        $('#wploti_note_notice').fadeOut('3000');
    });

    /**
     * toggle wploti activation via settings button
     */

    $('.wploti-maintenance-toggle #wploti-status').on('click', function () {
        var security = $(this).data('security');
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_toggle_activation',
                security: security,
                payload: 'toggle_wploti_status',
            },
            type: 'post',
            success: function (result, textstatus) {
                /* console.log(result);

                 console.log('sucess');*/
                $('.wploti-maintenance-toggle .wploti-status input[type=radio]').prop('disabled', function (_, val) {
                    return !val;
                });
                $('#wploti-toggle-adminbar').toggleClass('status-1');
                $('#wploti_main_options').fadeToggle();
                wploti_state();
                //$(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
            },
            error: function (result) {
                /* console.log(result);

                 console.log('fail');*/
            },
        })
    })

    /**
     * load animations by infinite scroll
     */

    var start = 0;
    var limit = 7;
    const step = 7;
    var action = 'inactive';
    var animations_count = $('.animations').attr('animations-count');

    function load_animations(start, limit) {
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wploti_animation_ajax_load',
                start: start,
                limit: limit,
            },
            type: 'post',
            success: function (data, textstatus) {
                $('.animations').append(data);
                if (limit >= animations_count) {
                    //$('#load-animations-message').html("<button type='button' class='button button-primary'>No More Data Found</button>");
                    $('#load-animations-message').html("");
                    action = 'active';
                } else {
                    $('#load-animations-message').html("<button type='button' class='button button-secondary'>" + wploti_translate.pls_wait + "....</button>");
                    action = "inactive"; // user action has been completed
                }
            },
            error: function (result) {
                /* console.log(result);

                console.log('fail'); */
            },
        })
    }
    if (action == 'inactive') {
        action = 'active';
        load_animations(start, limit);
    }
    $(window).scroll(function () {
        if ($(window).scrollTop() + $(window).height() > $(".animations").height() && action == 'inactive') {
            action = 'active';
            limit += step; // increase limit by step
            start += step; // increase counter by step
            /* if the limit counter has bypassed the number of animations

                reset the limit to the number of animations

            */
            if (limit >= limit - (limit % animations_count) && limit > animations_count) {
                limit = limit - (limit % animations_count);
            }
            setTimeout(function () {
                load_animations(start, limit);
            }, 2000);
        }
    });

    /**
     * RESET SETTINGS
     */

    $('.wploti_reset_settings').click(function () {
        var security = $(this).data('security');
        wploti_confirm.open({
            message: wploti_translate.be_careful + ' !<br><br>' + wploti_translate.option_reset_txt,
            onok: () => {
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'wploti_reset_settings',
                        security: security,
                    },
                    type: 'post',
                    success: function (result, textstatus) {
                        /* console.log(result);

                        console.log('sucess'); */
                        window.location.reload(true);
                    },
                    error: function (result) {
                        /* console.log(result);

                        console.log('fail'); */
                    },
                })
            }
        })
    })
});