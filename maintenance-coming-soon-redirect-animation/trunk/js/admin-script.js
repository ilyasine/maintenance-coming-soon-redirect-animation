// scroll to top on page load
window.onbeforeunload = function() {
    window.scrollTo(0, 0);
};


jQuery(document).ready(function ($) {

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

                /*console.log(result);
                console.log('sucess');*/

				$('#wploti-toggle-adminbar').toggleClass('status-1');
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

    $('input.wploti_header_type').on('change' , function() {
        
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
                console.log('sucess');*/

                $(".updated").fadeIn(1000).delay(7000).fadeOut("slow");

            },
            error: function (result) {
                /*console.log(result);
                 console.log('fail');*/
            },
        }) 
    })


    /**
     * save input message value 
     * 
     */

     $('input#wploti_message').on('change' , function() {
     
         setTimeout(() => {

            var message = $(this).val();
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
    
                   /* console.log(result);
                    console.log('sucess');*/

                    $(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
                   
    
                },
                error: function (result) {
                    /*console.log(result);
                    console.log('fail');*/
                },
            })
            
        }, 1000);

     })

     	
        /**
         *  (js) dismiss activation notice
         */
            
            $(document).on( 'click', '.wploti-activation-dismiss', function( event ) {
            event.preventDefault();

            var security = $(this).data('security');

            $.post( ajaxurl, {
                action: 'wploti_ajax_dismiss_activation_notice',
                security: security ,
            });
            $('#wploti_mr_enabled_notice').fadeOut('3000');
        });
     
					
        /**
         *  (js) dismiss activation notice
         */
            
         $('#wploti_note_notice .wploti-note-dismiss').on('click', function (event) {
            event.preventDefault();

            var security = $(this).data('security');

            $.post( ajaxurl, {
                action: 'wploti_ajax_dismiss_notes_notice',
                security: security ,
                
            });
            $('#wploti_note_notice').fadeOut('3000');
        });
        

    /**
     * toggle wploti activation via settings button
     * 
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

                $('.wploti-maintenance-toggle .wploti-status input[type=radio]').prop(
                    'disabled',
                    function (_, val) {
                        return !val;
                    }
                );
                $('#wploti-toggle-adminbar').toggleClass('status-1');
                $('#wploti_main_options').fadeToggle();
               
             
                $(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
               

                
  
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

    var start = 0 ;
    var limit = 7 ;
    const step = 7 ;
    var action = 'inactive';
    var animations_count = $('.animations').attr('animations-count');


    function load_animations(start, limit){
        
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

                    if( limit >= animations_count ) {                                   
                        //$('#load-animations-message').html("<button type='button' class='button button-primary'>No More Data Found</button>");
                        $('#load-animations-message').html("");
                        action = 'active';
                    }else {
                       
                        $('#load-animations-message').html("<button type='button' class='button button-secondary'>Please Wait....</button>");
                        action = "inactive"; // user action has been completed
                    }
            },
            error: function (result) {
                 /* console.log(result);
                 console.log('fail'); */
            },
        })

    }

    
        if( action == 'inactive' ){
            action = 'active';           
            load_animations(start, limit);
        }


        $(window).scroll(function(){

             if($(window).scrollTop() + $(window).height() > $(".animations").height() && action == 'inactive' ) {
                action = 'active';
               
                limit += step; // increase limit by step
                start += step; // increase counter by step

                /* if the limit counter has bypassed the number of animations
                    reset the limit to the number of animations
                */
                if(limit >= limit - (limit % animations_count)  && limit > animations_count) { 
                    limit = limit - (limit % animations_count) ;                     
                }    
            
                setTimeout(function(){
                    load_animations(start, limit);
                }, 2000);            
            } 

        });


    /**
     * RESET SETTINGS
     */
    
    $('.wploti_reset_settings').click(function(){	

        var security = $(this).data('security');
		
        wploti_confirm.open({

            message: 'Please Be careful ! <br><br>This option will reset all your selections to defaults options and will delete the IP addresses and access keys as well',
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
    