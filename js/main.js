// JavaScript Document
jQuery( document ).ready(function($) {
	
	$("#provensec_setting").validate({
		rules: 
		{
			provensec_api:  {required:true,rangelength: [25, 25] },
			provensec_user_name :{required:true},
			user_assets: {required:true,digits: true, min: 1 },
			user_asset_price : {required:true,number: true, min: 1 },
			provensec_paypal: { required: true, email: true }
		},
		messages: {
			provensec_api: {rangelength:"API key must be 25 characters long"}	
		}
		
	});	
	/*Validation Signup form ENDS*/
	
	/*Media uploader starts here*/
	  $('.my-media-uploader-button').click(function(e) {
		var send_attachment_bkp = wp.media.editor.send.attachment;
		wp.media.editor.send.attachment = function(props, attachment){
		  $(".my-media-uploader-input").val(attachment.url);
		  $("#logo_img").attr('src',attachment.url);
		  $("#logo_img").show();
	  }
	  wp.media.editor.open($(this));
	  return false;
	  });
	  
	/*Media uploader ends here*/	
	
		

});