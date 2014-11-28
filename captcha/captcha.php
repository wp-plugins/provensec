<?php
//staring session
session_start();
//Initializing PHP variable with string
$captchanumber = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz';
//Getting first 6 word after shuffle
$captchanumber = substr(str_shuffle($captchanumber), 0, 6);
//Initializing session variable with above generated sub-string
$_SESSION["code"] = $captchanumber;
//Generating CAPTCHA
$image = imagecreatefromjpeg("bj.jpg");
$foreground = imagecolorallocate($image, 175, 199, 200); //font color
//imagestring( $image,5, 55, 30, $captchanumber, $foreground );
$font = 'font.ttf';
imagettftext($image, 20, 0,  35, 50, $foreground, $font, $captchanumber);
header('Content-type: image/png');
imagepng($image);
?>