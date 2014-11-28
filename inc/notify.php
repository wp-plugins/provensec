<?php
require_once('ipn.class.php');
global $wpdb;
$setting =  get_option('provensec_setting');
$api_key =  $setting['api_key'];
$user_name =  $setting['user_name'];

if(isset($_POST['custom']))
{
	wp_mail(ADMIN_EMAIL,'Subject','messgae');
	
	$headers = array();
	
	$headers[] = 'From: '.get_bloginfo('name').' <'.ADMIN_EMAIL.'>';
	
	$data = $_POST;
					
	$listener = new IpnListener();
		
	$listener->use_sandbox = ENABLE_SANDBOX;
	
	try {
		$listener->requirePostMethod();
		$verified = $listener->processIpn($data);
	} catch (Exception $e) {
	    $error = $e->getMessage();
        mail(ADMIN_EMAIL, 'Invalid IPN error', $error);
	}
	$table_name = $wpdb->prefix . 'provensec_users';
	$user_data = $wpdb->get_row("SELECT * FROM $table_name WHERE id = '".$_POST['custom']."'");
	if ($verified && !empty($user_data)) {				
				
				$wpdb->update( $table_name,
					array( 'status' => 1,'payment_date' => date('Y-m-d H:i:s') ),
					array( 'id' => $_POST['custom'] )
				);		
				
				$subject = __('New user registered on '.get_bloginfo('name'),'provensec');
						
				$message = 'Hello admin, </br> New user registered on '.get_bloginfo('name').'. You can check details from wp admin.';

				wp_mail(ADMIN_EMAIL, $subject, $message, $headers );	
				
				$post_array = array(
							'name' => $user_data->name,
							'email' => $user_data->email,
							'password' =>'testpass',    
							'organization_name' => $user_data->org_name ,
							'asset' => $user_data->number_asset,
							'address' => $user_data->address,
							'postcode'=>$user_data->postcode,
							'city' =>$user_data->city,
							'country' =>$user_data->country
				);
								
				$post_array =  http_build_query($post_array);
				$url = PROVENSEC_SERVICES."register/";
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER,array('Authorization: TRUEREST username='.$user_name.'&apikey='.$api_key));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_array);
				$output = curl_exec($ch);
				$info = curl_getinfo($ch);
				curl_close($ch);
	}
	else /*If not verified*/
	{
		wp_mail(ADMIN_EMAIL,'Invalid IPN error','Invalid IPN error', $headers );
	}	
}
else
{
	echo 'Invalid Access !';	
}