<?php
/*
Plugin Name: Provensec
Plugin URI: http://provensec.com/
Description: It's used to provide sign up form to get registered via API for Scanning services, provensec.com provide dashboard to registered used to add there asset and finding found for assets.
Author: provensec
Version: 1.0.1
Author URI: http://provensec.com/
*/
defined('ABSPATH') or die("Direct access not allowed");
define('PROVENSEC_URL',plugins_url('/',__FILE__));
define('PROVENSEC_PAYPAL_LIVE','https://www.paypal.com/cgi-bin/webscr?');
define('PROVENSEC_PAYPAL_SANDBOX','https://www.sandbox.paypal.com/cgi-bin/webscr?');
define('ENABLE_SANDBOX',false);
define('ADMIN_EMAIL',get_bloginfo('admin_email'));
define('PROVENSEC_SERVICES','http://cloudprox.provensec.com/servers/');

/*Register activation Hook start*/
function provensec_activate() {
	// Activation code here...
	
	provensec_create_pages();
	
	$def_setting = array(
						'api_key'=>'',
						'assets_number'=>'',
						'price_per_asset'=>'',
						'paypal_email'=>'',
						'paypal_logo'=>'',
						'enable_capthca'=>'',
						'ipn_page' => ''
	);
	
	$setting =  get_option('provensec_setting');
	
	if($setting==false)
	{
		update_option('provensec_setting',$def_setting);	
	}		
	elseif(!empty($setting))
	{
		$setting_api = $setting['api_key'];
		if(empty($setting_api))
		{
			update_option('provensec_setting',$def_setting);	
		}
	}	
	/* Create database tables */
	
	create_tables();
	/* Create pages */	
	
	
	
}

register_activation_hook( __FILE__, 'provensec_activate' );

function create_tables()
{
	global $wpdb;

	$table_name = $wpdb->prefix . 'provensec_users';
	
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

      $sql = "CREATE TABLE " . $table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				email varchar(120) NOT NULL,
				org_name text NOT NULL,
				number_asset int(11) NOT NULL,
				total_amount float,
				created_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				payment_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				transaction_id varchar(100),
				address varchar(255),
				city varchar(45),
				postcode varchar(15),
				country varchar(60),
				status int(11),
				payment_code varchar(255),
     			PRIMARY KEY  (id)
      );";
      
	  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	  	  	  
      dbDelta($sql);
   }
		
	
}



function provensec_create_pages()
{
	$is_page = get_option('provensec_signup_page');
	
	if(empty($is_page) || get_post_status( $is_page ) != 'publish')
	{
		$page_args = array(
		'post_title'    => __('Provensec Signup','provensec'),
		'post_content'  => '[provensec-registration-form]',
		'post_status'   => 'publish',
		'post_type'=>'page'
		);
		
		$page_id = wp_insert_post( $page_args );	
			
		update_option('provensec_signup_page',$page_id);		
	}
	
	$is_page1 = get_option('provensec_success_page');
	if(empty($is_page1) || get_post_status( $is_page1 ) != 'publish')
	{
		$page_args = array(
		'post_title'    => __('Provensec Success','provensec'),
		'post_content'  => '<div class="notice_success">'.__('Congratulation. Your payment have been done successfully.','provensec').'</div>',
		'post_status'   => 'publish',
		'post_type'=>'page'
		);
		
		$page_id1 = wp_insert_post( $page_args );
					
		update_option('provensec_success_page',$page_id1);		
	}	
	
	$is_page2 = get_option('provensec_cancel_page');
	
	if(empty($is_page2) || get_post_status( $is_page2 ) != 'publish')
	{
		$page_args = array(
		'post_title'    => __('Provensec payment cancel','provensec'),
		'post_content'  => '<div class="notice_error">'.__('You have cancelled your payment.','provensec').'</div>',
		'post_status'   => 'publish',
		'post_type'		=> 'page'
		);
				
		$page_id2 = wp_insert_post( $page_args );
					
		update_option('provensec_cancel_page',$page_id2);
	}	
	
	/*Create IPN page */
	$ipn_page = get_option('provensec_ipn_page');	
	if(empty($ipn_page) || get_post_status( $ipn_page ) != 'publish' )
	{
		$page_args3 = array(
		'post_title'    => __('Provensec IPN','provensec'),
		'post_content'  => '<div class="notice_error">'.__('This is provensec IPN page.','provensec').'</div>',
		'post_status'   => 'publish',
		'post_type'		=> 'page'
		);
						
		$page_id3 = wp_insert_post( $page_args3 );
		update_option('provensec_ipn_page',$page_id3);	
	}
	
		
		
}

/*Register activation Hook ends*/
/** Step 2 (from text above). */

add_action( 'admin_menu', 'provensec_menu' );

/** Step 1. */
function provensec_menu() {
	
	add_menu_page( 'Provemsec Option', 'Provensec', 'manage_options', 'provensec-setting', 'provensec_plugin_options',PROVENSEC_URL.'images/plugin_logo.png' );
	add_submenu_page('provensec-setting','Provensec Setting', 'Setting', 'manage_options', 'provensec-setting', 'provensec_plugin_options' ); 
	add_submenu_page('provensec-setting','Provensec User List', 'User List', 'manage_options', 'provensec-users', 'provensec_plugin_options_submenu' );

}

/*Load text domain to recieve translated strings*/
add_action( 'plugins_loaded', 'provensec_load_textdomain' );

function provensec_load_textdomain() {
  load_plugin_textdomain( 'provensec', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function db($x)
{
	echo '<pre>';
	print_r($x);
	echo '</pre>';
}

function initialize_plugin()
{
	session_start();
	
	global $wpdb;
	
	$setting  = get_option('provensec_setting');
	
	$ipn_page = get_option('provensec_ipn_page');
	
	$ipn_url = get_permalink($ipn_page);
	
	extract($setting);	
	
	$table_name = $wpdb->prefix . 'provensec_users';
		
	if(isset($_GET['provensec_do_payment']) && !empty($_GET['provensec_do_payment'])) 
	{
		$payment_code = $_GET['provensec_do_payment'];	
			
		$data = $wpdb->get_row('SELECT * FROM '.$table_name.' WHERE md5(id)="'.$payment_code.'"');

		if(!empty($data))
		{
			$paypal_query = array();
			$paypal_query['custom'] = $data->id ;
			$paypal_query['return'] = site_url().'?provensec_ipn_success=1';
			$paypal_query['notify_url'] = $ipn_url;
			$paypal_query['cancel_return'] = site_url().'?provensec_ipn_cancel=1';	
			$paypal_query['cmd'] = '_cart';
			$paypal_query['upload'] = '1';
			$paypal_query['business'] = $paypal_email;
			$paypal_query['first_name'] = $data->name;
			$paypal_query['email'] = $data->email;
			$paypal_query['address1'] = $data->address;
			$paypal_query['city'] = $data->city ;
			$paypal_query['zip'] = $data->postcode ;
			$paypal_query['item_name_1'] = get_bloginfo('name').' '.__('Registration','provensec');
			$paypal_query['quantity_1'] = $data->number_asset ;
			$paypal_query['amount_1'] = $price_per_asset ;
			$paypal_query['image_url'] = $paypal_logo ;
			$paypal_query['rm'] = 2 ;
						
			$query_string = http_build_query($paypal_query);
						
			if(ENABLE_SANDBOX==true) {
				$paypal_url = PROVENSEC_PAYPAL_SANDBOX;
			}
			else {
				$paypal_url = PROVENSEC_PAYPAL_LIVE;	
			}
			header('Location: '.$paypal_url . $query_string);	
			exit;
		}		
	}

	/*If payment success*/
	if(isset($_GET['provensec_ipn_success']) && $_GET['provensec_ipn_success']==1) {		
		$success_page_id = get_option('provensec_success_page');
		$success_page 	 = get_permalink($success_page_id);
		
		if(isset($_SESSION['userid']) && $_SESSION['userid'] > 0) {			
			$user_data = $wpdb->get_row("SELECT * FROM $table_name WHERE id = ".$_SESSION['userid']);		
			if($user_data->status==1)
			{
				header('Location: '.$success_page);	
				exit;
			}			
		}
		exit;
	}
	
	/*If payment cancel*/
	if(isset($_GET['provensec_ipn_cancel']) && !empty($_GET['provensec_ipn_cancel'])) 
	{
		$wpdb->delete($table_name,array('id'=> $_SESSION['userid'],'status'=>0));		
		delete_users();
		$canel_page_id = get_option('provensec_cancel_page');	
		$cancel_page = get_permalink($canel_page_id);
		header('Location: '.$cancel_page);
		exit;
	}

}

add_action('init', 'initialize_plugin', 1);

function signup_user()
{
	require('inc/validation.php');	
	GUMP::set_field_name("provensec_name", "Name");
	GUMP::set_field_name("provensec_pass", "Password");
	GUMP::set_field_name("provensec_email", "Email");
			
	$is_valid = GUMP::is_valid($_POST, array(	
		'provensec_name'  => 'required|min_len,6',
		'provensec_pass'  => 'required|max_len,20|min_len,6',
		'provensec_email' => 'required|valid_email'
	));
	
	extract($_POST);
	
	$setting = get_option('provensec_setting');	
		
	extract($setting);
	
	$captcha_error = '';	
	
	/*Validate the API */
		/*CAPTCHA validation */	
		
	if(isset($_POST['captcha']) && $_SESSION["code"] != $_POST['captcha'])
	{
		$is_valid= array();
		$is_valid[] = __('Entered captcha is incorrect','provensec');	
	}
	
	if($is_valid ===true)
	{
		
	$post_array = array(
	   'name' => $provensec_name,
	   'email' => $provensec_email ,
	   'password' => $provensec_pass ,    
	   'organization_name' => $provensec_org ,
	   'asset' => $provensec_assets,	   
	   'address' => $provensec_add,
	   'postcode'=>$provensec_postcode,
	   'city' =>$provensec_city,
	   'country' =>$provensec_country ,
	   'is_verification' => '1'  
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
	$api_data = json_decode($output);
	if(empty($api_data))
	{
		$is_valid= array();
		$is_valid[] = __('Unable to process your request ,Please try again!','provensec');
	}
	else
	{
		
		$api_statuss =  $api_data->meta->status;
						
		if($api_statuss=='error')
		{
			$is_valid= array();
			$is_valid[] =  $api_data ->meta->feedback[0]->message;	
		}
		elseif($api_statuss=='ok')
		{
			$is_valid = true;	
		}
	}	
	
	}
	
	/*API validation ends here*/
		
	if($is_valid === true) 
	{		
		global $wpdb;
					
		$table_name = $wpdb->prefix . 'provensec_users';
			
		$payment_code = md5(rand().date('Y-m-d H:i:s'));
					
		$provensec_total_payment = 	$provensec_assets * $price_per_asset ;
		
		$wpdb->insert( $table_name, 
		array( 
			'name' => $provensec_name ,
			'email' => $provensec_email ,
			'org_name' => $provensec_org ,
			'number_asset' => $provensec_assets ,
			'total_amount' => $provensec_total_payment,
			'created_date' => date('Y-m-d H:i:s') ,		
			'address' => $provensec_add ,
			'city' => $provensec_city ,
			'postcode' => $provensec_postcode ,
			'country' => $provensec_country ,
			'status' => 0			
		) );

		$user_id = $wpdb->insert_id;
				
		if($user_id > 0)
		{	
			$_SESSION['userid'] = $user_id;
			echo '<div class="notice_loader">';
			echo '<img id="provensec_loader" src="'.plugins_url('/images/loader.GIF', __FILE__).'"/>';
			_e('You will be redirected to payment method shortly .....','provensec');
			echo '</div>';			
			echo '<style>#frm_provensec_signup{display:none;}</style>';
			echo '<meta http-equiv="refresh" content="5;URL='.site_url().'?provensec_do_payment='.md5($user_id).'">';
		}
	} 
	else
	{
		echo '<ul class="error_notice_area">';
		foreach($is_valid as $error)
		{
			echo '<li>'.$error.'</li>';
		}				
		echo '</ul>';
	}
		
	
}

function load_shortcode_scripts()
{
	$list = 'enqueued';
			
	if (wp_script_is('jquery', $list )== false) {
		wp_enqueue_script('jquery');
	}
	
	wp_enqueue_style('provensec_style', plugins_url( '/style.css', __FILE__ ));	
	wp_enqueue_script('provensec_validate', plugins_url( 'js/validate.js', __FILE__ ),'',array('jquery'));	
	wp_register_script('provensec_shortcode_js',plugins_url( '/js/shortcode.js', __FILE__ ) );
	$translation_array = array( 'captcha' => plugins_url('/captcha/captcha.php', __FILE__) );
	wp_localize_script( 'provensec_shortcode_js', 'url', $translation_array );
	
	wp_enqueue_script('provensec_shortcode_js');	
}

add_action( 'wp_enqueue_scripts', 'load_shortcode_scripts' );

/*Shortcode for signup form*/
function provensec_shortcode()
{
	$api = get_option('provensec_signup_page');
			
	if(isset($_POST['provensec_submit'])) {
		signup_user();
	}	
	
	$return = '';
			
	$return .= '<div class="provensec_wrap">';
	
	$setting = get_option('provensec_setting');
	
	extract($setting);
	
	$perma = trim(get_option('permalink_structure'));
      if (empty($perma)) {
        return '<div class="error"><p><b><em>' . __('Permalinks Error', 'easy-paypal') . '</em></b>: ' . sprintf(__('You need to set your %s to something other than the default for <em>Provensec</em> to work properly. You can set it at %s', 'provensec'), '<a href="http://codex.wordpress.org/Using_Permalinks" target="_blank">permalinks</a>', '<a href="options-permalink.php">Settings &rarr; Permalinks</a>.</p></div><br />');
      }
		
	$return .= '<form name="frm_provensec_signup" id="frm_provensec_signup" method="post">';
	
	$return .= '<label class="provensec_label" for="provensec_name">'.__('Name','provensec').'<span class="req_aterick">*</span></label><input type="text" name="provensec_name" id="provensec_name" class="prov_textbox"  value="'.get_provensec_postvalue('provensec_name').'"/>';
	
	$return .= '<label class="provensec_label" for="provensec_email">'.__('Email','provensec').'<span class="req_aterick">*</span></label><input type="text" name="provensec_email" id="provensec_email" class="prov_textbox" value="'.get_provensec_postvalue('provensec_email').'"/>';
	
	$return .= '<label class="provensec_label" for="provensec_pass">'.__('Password','provensec').'<span class="req_aterick">*</span></label><input type="password" name="provensec_pass" id="provensec_pass" class="prov_textbox" value="'.get_provensec_postvalue('provensec_pass').'"/>';
	
	$return .= '<label class="provensec_label" for="provensec_confirm_pass">'.__('Confirm Password','provensec').'<span class="req_aterick">*</span></label><input type="password" name="provensec_confirm_pass" id="provensec_confirm_pass" class="prov_textbox" value="'.get_provensec_postvalue('provensec_confirm_pass').'"/>';
	
	$return .= '<label class="provensec_label" for="provensec_org">'.__('Organisation Name','provensec').'<span class="req_aterick">*</span></label><input type="text" name="provensec_org" id="provensec_org" class="prov_textbox" value="'.get_provensec_postvalue('provensec_org').'" />';
	
	$return .= '<label class="provensec_label" for="provensec_add">'.__('Address','provensec').'<span class="req_aterick">*</span></label><input type="text" name="provensec_add" id="provensec_add" class="prov_textbox"  value="'.get_provensec_postvalue('provensec_add').'"/>';
	
	$return .= '<label class="provensec_label" for="provensec_city">'.__('City','provensec').'<span class="req_aterick">*</span></label><input type="text" name="provensec_city" id="provensec_city" class="prov_textbox" value="'.get_provensec_postvalue('provensec_city').'"/>';
	
	$return .= '<label class="provensec_label" for="provensec_postcode">'.__('Postcode','provensec').'<span class="req_aterick">*</span></label><input type="text" name="provensec_postcode" id="provensec_postcode" class="prov_textbox" value="'.get_provensec_postvalue('provensec_postcode').'" />';
	
	$country_array = array("Afghanistan", "Aland Islands", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Barbuda", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Trty.", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Caicos Islands", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "French Guiana", "French Polynesia", "French Southern Territories", "Futuna Islands", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guernsey", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard", "Herzegovina", "Holy See", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Isle of Man", "Israel", "Italy", "Jamaica", "Jan Mayen Islands", "Japan", "Jersey", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea", "Korea (Democratic)", "Kuwait", "Kyrgyzstan", "Lao", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macao", "Macedonia", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "McDonald Islands", "Mexico", "Micronesia", "Miquelon", "Moldova", "Monaco", "Mongolia", "Montenegro", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "Nevis", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Palestinian Territory, Occupied", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Principe", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Barthelemy", "Saint Helena", "Saint Kitts", "Saint Lucia", "Saint Martin (French part)", "Saint Pierre", "Saint Vincent", "Samoa", "San Marino", "Sao Tome", "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia", "South Sandwich Islands", "Spain", "Sri Lanka", "Sudan", "Suriname", "Svalbard", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan", "Tajikistan", "Tanzania", "Thailand", "The Grenadines", "Timor-Leste", "Tobago", "Togo", "Tokelau", "Tonga", "Trinidad", "Tunisia", "Turkey", "Turkmenistan", "Turks Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "Uruguay", "US Minor Outlying Islands", "Uzbekistan", "Vanuatu", "Vatican City State", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (US)", "Wallis", "Western Sahara", "Yemen", "Zambia", "Zimbabwe");
	
	$return .= '<label class="provensec_label" for="provensec_country">'.__('Country','provensec').'<span class="req_aterick">*</span></label>';
	
	$return .= '<select name="provensec_country" id="provensec_country">';

	$country = get_provensec_postvalue('provensec_country');
	foreach($country_array as $item)
	{
		$select = '';
		if($item==$country)
		{
			$select = ' SELECTED ';	
		}
		$return .='<option value="'.$item.'" '.$select.'>'.$item.'</option>';
	}

	$return .= '</select>';
	
	$assets = get_provensec_postvalue('provensec_assets');
	
	$return .= '<label class="provensec_label" for="provensec_assets">'.__('Assets','provensec').'<span class="req_aterick">*</span></label>';	
	$return .= '<input type="hidden" name="price_per_asset" id="price_per_asset" value="'.$price_per_asset.'" />';

	$return .= '<div class="provensec_select"><select name="provensec_assets" id="provensec_assets">';	

	for($i=1; $i<= $assets_number; $i++)
	{
		$select = '';
		if($i == $assets)
		{
			$select = ' SELECTED ';	
		}
		$return .='<option value="'.$i.'" '.$select.'>'.$i.'</option>';
	}	
	$return .= '</select></div>';
	
	$return .= '<div class="total_payment_lbl">'.__('Total payment : ','provensec').'<span name="provensec_total_payment" id="provensec_total_payment">$0.0</span></div>';

	/*Enable captcha*/
	if($enable_capthca=='on') {

	$return .= '<div id="imgdiv"><img id="img" src="'.plugins_url('/captcha/captcha.php', __FILE__).'" />
	<img id="reload" src="'.plugins_url('/captcha/reload.png', __FILE__).'" /></div>';
		
	$return .= '<p><label class="provensec_label" for="captcha">'.__('Enter image text','provensec').'<span class="req_aterick">*</span></label><input type="text" name="captcha" id="captcha" class="prov_textbox capthca_text" /></p>';

	}

	$return .= '<p><input type="submit" name="provensec_submit" id="provensec_submit" value="'.__('Submit','provensec').'"/></p>';

	$return .= '</form>';
		
	$return .='</div>';
	
	return  $return;

}

function get_provensec_postvalue($key)
{
	if(isset($_POST[$key]))
	{
		return 	strip_tags(trim($_POST[$key]));
	}
}

add_shortcode( 'provensec-registration-form' ,'provensec_shortcode' ); 

/*Function load CSS and JS on plugin admin pages */
function load_admin_scripts()
{
	 //wp_register_style( 'provensec_style', plugins_url( '/style.css', __FILE__ ) );
	 wp_enqueue_style('provensec_style', plugins_url( '/style.css', __FILE__ ));	 
	 //wp_enqueue_script('jquery');
	 wp_enqueue_media();	
	 wp_register_script( 'provensec_validation', plugins_url( 'js/validate.js', __FILE__ ),'',array('jquery') );
	 wp_enqueue_script('provensec_validation');
	 wp_register_script( 'provensec_main', plugins_url( 'js/main.js', __FILE__ ) ,'',array('jquery'));
	 wp_enqueue_script('provensec_main');
}

/*Function to check vald mail*/
function is_valid_email($email)
{
	$regex = "^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$";
	if ( preg_match( $regex, $email ) ) {
		return true;
	} else { 
		return false;
	}
}


function provensec_plugin_options_submenu()
{
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	load_admin_scripts();
	
	if(isset($_GET['tab']))
	{
		$current_tab = $_GET['tab'];	
	}else
	{
		$current_tab = 'users';
	}
	
	$tabs = array( 'options' => 'Setting', 'users' => 'Registered Users');
    echo '<div id="icon-themes"></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current_tab ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='?page=provensec-setting&tab=$tab'>$name</a>";
    }
    echo '</h2>';

	if($current_tab=='options')
	{
		provensec_setting_page();
	}
	elseif($current_tab =='users')
	{
		display_user_list();
	}
		
}

/** Step 3. */
function provensec_plugin_options() {
	
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	load_admin_scripts();
	
	if(isset($_GET['tab']))
	{
		$current_tab = $_GET['tab'];	
	}else {
		$current_tab = 'options';
	}
	
	$tabs = array( 'options' => 'Setting', 'users' => 'Registered Users');
    echo '<div id="icon-themes"></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current_tab ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='?page=provensec-setting&tab=$tab'>$name</a>";
    }
    echo '</h2>';

	if($current_tab=='options')
	{
		provensec_setting_page();
	}
	elseif($current_tab =='users')
	{
		display_user_list();
	}

}

function provensec_setting_page()
{
	if(isset($_POST['provensec_savesetting']))	
	{		
		extract($_POST);

		$provensec_setting = array(
								'api_key'=>$provensec_api,
								'user_name'=>$provensec_user_name,
								'assets_number'=>$user_assets,
								'price_per_asset'=>$user_asset_price,
								'paypal_email'=>$provensec_paypal,
								'paypal_logo'=>$paypal_logo,
								'enable_capthca'=>$enable_captcha
		);

		if(!empty($provensec_success_page))
		{
			update_option('provensec_success_page',$provensec_success_page);
		}

		update_option('provensec_setting',$provensec_setting);

		echo '<div class="updated"><p>'.__('Setting saved successfully','provensec').'</p></div>';		
	}	
	
	$perma = trim(get_option('permalink_structure'));
      if (empty($perma)) {
        echo '<div class="error"><p><b><em>' . __('Permalinks Error', 'easy-paypal') . '</em></b>: ' . sprintf(__('You need to set your %s to something other than the default for <em>Provensec</em> to work properly. You can set it at %s', 'provensec'), '<a href="http://codex.wordpress.org/Using_Permalinks" target="_blank">permalinks</a>', '<a href="options-permalink.php">Settings &rarr; Permalinks</a>.</p></div><br />');
      }

?>

<div class="wrap">
  <?php 
	$provensec_data = get_option('provensec_setting');
	extract($provensec_data);
	$logo_class = '';
	if(empty($paypal_logo)){ $logo_class = ' hide'; }		
  ?>
  <h2>
    <?php _e('Setting','provensec'); ?>
  </h2>
  <form name="provensec_setting" id="provensec_setting" action="" method="post">
    <label for="provensec_api" class="clear provensec_label">
      <?php _e('API Key','provensec'); ?>
    </label>
    <input type="text" name="provensec_api" id="provensec_api" class="prov_textbox" value="<?php echo $api_key;?>"/>
    <span class="helptext">
    <?php _e('Enter 25 characters API key provided by provensec','provensec'); ?>
    </span>
    <label for="provensec_user_name" class="clear provensec_label">
      <?php _e('Username','provensec'); ?>
    </label>
    <input type="text" name="provensec_user_name" id="provensec_user_name" class="prov_textbox" value="<?php echo $user_name;?>"/>
    <span class="helptext">
    <?php _e('Enter the email id provided for above API key','provensec'); ?>
    </span>
    <label for="user_assets" class="clear provensec_label">
      <?php _e('Number of assets','provensec'); ?>
    </label>
    <input type="text" name="user_assets" id="user_assets" class="prov_textbox" value="<?php echo $assets_number;?>"/>
    <span class="helptext">
    <?php _e('Add max number of assets you want to show in drop down on registration form','provensec'); ?>
    </span>
    <label for="user_asset_price" class="clear provensec_label">
      <?php _e('Price per asset ($)','provensec'); ?>
    </label>
    <input type="text" name="user_asset_price" id="user_asset_price" class="prov_textbox" value="<?php echo $price_per_asset;?>"/>
    <span class="helptext">
    <?php _e('Add price per asset you want to charge','provensec'); ?>
    </span>
    <label for="provensec_paypal" class="clear provensec_label">
      <?php _e('Paypal email','provensec'); ?>
    </label>
    <input type="text" name="provensec_paypal" id="provensec_paypal" class="prov_textbox" value="<?php echo $paypal_email;?>"/>
    <span class="helptext">
    <?php _e('Enter business paypal account email address','provensec'); ?>
    </span>
    <label class="clear provensec_label">
      <?php _e('Upload logo for paypal','provensec'); ?>
    </label>
    <p>
      <input type="hidden" class="my-media-uploader-input" id="paypal_logo" name="paypal_logo" value="<?php if(!empty($paypal_logo)){ echo $paypal_logo; }?>"/>
      <input type="button" class="my-media-uploader-button btn btn-small" value="Upload"/>
      <img class="<?php echo $logo_class;?>" src="<?php if (getimagesize($paypal_logo) !== false) {echo $paypal_logo; }?>" id="logo_img"/> </p>
    <div style="clear:both;height:20px;"></div>
    <input type="checkbox" name="enable_captcha" id="enable_captcha"  <?php if($enable_capthca =='on'){echo 'checked';} ?>/>
    <label for="enable_captcha" style="display:inline;" class="provensec_label">
      <?php _e('Enable captcha','provensec'); ?>
    </label>
    <p>   
    </p>
    <p> 
    <label for="provensec_success_page" class="provensec_label">
      <?php _e('Payment success page','provensec'); ?>
    </label>
    <?php 
		$args = array(
			'sort_order' => 'ASC',
			'sort_column' => 'post_title',
			'offset' => 0,
			'post_type' => 'page',
			'post_status' => 'publish'
		); 
		
		$pages = get_pages($args);
		
		$success_page = get_option('provensec_success_page');
		
		if(!empty($pages))
		{
			echo '<select name="provensec_success_page" id="provensec_success_page">';
			
			foreach($pages as $page)
			{
				$select = '';
				if($success_page == $page->ID)
				{
					$select = ' SELECTED';
				}
				echo '<option value="'.$page->ID.'" '.$select.'>'.$page->post_title.'</option>';	
			}
			
			echo '</select>';
		}
	?>
    <br />
    <span class="helptext">
    <?php _e('Select the page to show redirect after successfull payment','provensec'); ?>
    </span>
    </p>
    <p>
      <input class="btn" type="submit" name="provensec_savesetting" id="provensec_savesetting" value="<?php _e('Save','provensec'); ?>" />
    </p>
  </form>
  <div class="right_block">
    <?php 
		$signup_page_id = get_option('provensec_signup_page');		
	?>
    <h3><a target="_blank" href="<?php echo get_permalink($signup_page_id);?>">
      <?php _e('Click here to view the registration page','provensec');?>
      </a></h3>
    <?php _e('You can also show registration form on any page or post using below shortcode :','provensec');?>
    <strong> <h3>[provensec-registration-form]</h3></strong> </div>
</div>
<?php 
}

if( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class My_List_Table extends WP_List_Table {

function table_data()
{
	global $wpdb;
	$tbl_mail = $wpdb->prefix.'provensec_users';
	
	$data =  $wpdb->get_results('select * from '.$tbl_mail .' where status=1 ORDER BY id DESC');
	$example_data = array();
	foreach($data as $k=>$val)
	{
		$item = array();
		if($val->status==1) {$status ='Sent'; } else{ $status='Scheduled'; }
		$item['id'] = $val->id;
		$item['name'] 		  = $val->name;
		$item['email'] 		  = $val->email;
		$item['org_name'] = $val->org_name;		
		$item['number_asset'] = $val->number_asset;
		$item['total_amount'] = $val->total_amount;
		$item['created_date'] = $val->created_date;
		$address = $val->address;
		$city = $val->city;
		$postcode = $val->postcode;
		$country = $val->country;
		$com_address = $address.'</br>'.$city.' , '.$postcode. '</br>'. $country;
		$item['address'] = $com_address;
		$item['payment_date'] =$val->payment_date;				
		$example_data[]= $item;	
	}
	
	return $example_data;
				
}
	
function get_columns(){
  $columns = array(  
    'name'    => __('Name','provensec'),
	'email'=>__('Email','provensec'),
    'org_name'      => __('Organisation','provensec'),	
	'address' => __('Address','provensec'),
	'number_asset'=>__('Number of assets','provensec'),
	'total_amount' => __('Total amount ($)','provensec'),
	'created_date' => __('Created date','provensec'),
	'payment_date' => __('Payment date','provensec')	
  );
    
  return $columns;
  
}

function column_name($item) {
  $actions = array(     
            'delete'    => sprintf('<a href="?page=%s&action=%s&tab=users&userid=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']));

  return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions) );
}

function prepare_items() {
	$per_page = 10;
	$current_page = $this->get_pagenum();
	$total_items = count($this->table_data());
	$this->found_data = array_slice($this->table_data(),(($current_page-1)*$per_page),$per_page);
	$this->set_pagination_args( array(
		'total_items' => $total_items,                 //WE have to calculate the total number of items
		'per_page'    => $per_page                     //WE have to determine how many items to show on a page
	) );
	
	$columns = $this->get_columns();
	$hidden = array();
	$sortable = $this->get_sortable_columns();
	$this->_column_headers = array($columns, $hidden, $sortable);
	$this->items = $this->found_data;
  
}

function get_sortable_columns() {
  $sortable_columns = array(
   /* 'email' => array('id',false)*/
  ); 
  return $sortable_columns;
}

function column_default( $item, $column_name ) {
  switch( $column_name ) {
    case 'name':
    case 'email':
    case 'org_name':
	case 'number_asset':
	case 'total_amount':
	case 'address':
	case 'created_date':
	case 'payment_date':		
      return $item[ $column_name ];
    default:
      return print_r( $item, true );
  }
}	
	
}
/*Class table ends*/
function display_user_list()
{
	delete_users();
	
	if(isset($_GET['action']) && !empty($_GET['action'])) 
	{
		$userid =  $_GET['userid'];
		if(isset($userid) && !empty($userid))
		{
			global $wpdb;
			$tbl_mail = $wpdb->prefix.'provensec_users';
			if($wpdb->delete($tbl_mail , array( 'id' => $userid ) ))
			{
				echo '<div class="updated"><p>'.__('User deleted successfully','provensec').'</p></div>';	
			}
		}
	}
	echo '<form name="show_greetings" id="show_greetings" method="get">';
	$myListTable = new My_List_Table();
	echo '<div class="wrap"><h2>'.__('Registered Users List','provensec').'</h2>'; 
	$myListTable->prepare_items(); 
	$myListTable->display();
	echo '</div></form>';
}

function delete_users()
{
	global $wpdb;
	$tbl_mail = $wpdb->prefix.'provensec_users';
	$lastdate = strtotime(' -1 day');
	$result = $wpdb->query( "DELETE FROM ".$tbl_mail." WHERE status =0 AND DATE(created_date) <= ".$lastdate );
}

add_filter( 'template_include', 'portfolio_page_template', 99 );

function portfolio_page_template( $template ) {		
	$ipn_page = get_option('provensec_ipn_page');
	if ( is_page($ipn_page)) {
		$ipn_template =  dirname( __FILE__ ) .'/inc/notify.php';
		return $ipn_template;
	}
	return $template;
	
}