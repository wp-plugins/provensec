jQuery( document ).ready(function($) {
	
	

	/*Validate signup form*/
		
	$("#frm_provensec_signup").validate({
		rules: 
			{
			provensec_name		:	"required",
			provensec_pass 		:	{required:true, minlength: 6 },
			provensec_confirm_pass	: 	{ equalTo: "#provensec_pass"},
			provensec_email		:	{ required: true, email: true },	
			provensec_org 		:	{required:true },	
			provensec_add 		:  	{required:true ,minlength: 10 },
			provensec_city 		: 	{required:true ,minlength: 3},
			provensec_postcode 	:	{required:true,digits: true, rangelength: [2, 8] },
			provensec_country 	:	{required:true },
			captcha				:	{required:true },
			},
			messages: {
				provensec_confirm_pass :{equalTo:"Password does not match"}
			}
	});
	
	function cal_paymeent()
	{
		var number_of_asset=  $('#provensec_assets').val();	
		var price_per_asset  = $('#price_per_asset').val();
		var total_payment = number_of_asset * price_per_asset;
		$('#provensec_total_payment').html('$'+total_payment.toFixed(2));	
	}
	
	cal_paymeent();
	
	$('#provensec_assets').on('change', function() {
		cal_paymeent();
		/*var number_of_asset=  this.value;	
		var price_per_asset  = $('#price_per_asset').val();
		var total_payment = number_of_asset * price_per_asset;
		$('#provensec_total_payment').html('$'+total_payment.toFixed(2));*/
	});
	
	/* Validate signup form completed */
	
	//change CAPTCHA on each click or on refreshing page
    $("#reload").click(function() {
		$("#img").attr('src', $("#img").attr('src')+'?'+Math.random());		
    });	

	
	
});