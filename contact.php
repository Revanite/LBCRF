---
layout: contact
---


<?php 
include "./libs/class.smtp.php";
include "./libs/class.phpmailer.php";
include "./simple-php-captcha.php";

//SMTP/Mail server settings
$SMTP_SERVER = ''; 
$SMTP_PORT = ''; 
$SMTP_USER = '';
$SMTP_PASS =  '';  
$FROM_EMAIL = '';
$FROM_NAME = '';
$TO_EMAIL = '';

//Website name
$WEBSITE = "";

//Airtable related setting
$API_KEY = '{{site.airtable.apikey}}';

//Airtable API URL
$AIRTABLE_URL = "https://api.airtable.com/v0/{{ site.airtable.contact }}/Contact_Responses";

$message = '';

if (empty($_POST['send'])){
    $_SESSION['captcha'] = simple_php_captcha();
}


if(isset($_POST) and $_POST['send'] == "Send" ) {
    $name = mysql_escape_string($_POST['name']);
    $email = mysql_escape_string($_POST['email']);   
    $phone = mysql_escape_string($_POST['phone']);  
    $message = mysql_escape_string($_POST['message']);     
    $error = array();
    $captcha = mysql_escape_string($_POST['captcha']);    

    if (strtolower($captcha) == strtolower($_SESSION['captcha']['code'])) {

	    if(empty($name) ||  empty($email) ){
		$error['mail'] = "Name or Email value missing!!";
	    }   
	    
	    if(count($error) == 0){
			//send email
			$msg = "";
			$msg .= "Someone visited the church website and left you a message:\n\n";
			$msg .= "Message from: ".$name."\n";
			$msg .= "Email: ".$email."\n";
			$msg .= "Phone: ".$phone."\n\n";
			$msg .= "----------------------\n\n";
			$msg .= $message;
			
			$mail = new PHPMailer();
			$mail->IsSMTP();
			$mail->SMTPAuth = true;
			//$mail->SMTPSecure = "tls";
			//$mail->SMTPDebug = true;
		
			$mail->Host = $SMTP_SERVER;
			$mail->Port = $SMTP_PORT;
			$mail->Username = $SMTP_USER;
			$mail->Password = $SMTP_PASS;
				
			$mail->SetFrom($FROM_EMAIL, $FROM_NAME);
			$mail->AddReplyTo("", "");
		    
			$mail->Subject = "New message from the church website: ".$WEBSITE;

			$mail->AddAddress($TO_EMAIL, $TO_EMAIL);
		
			$mail->Body = $msg;
			if ($mail->Send()) {
			    $emailmsg = "Email sent.";
			}
		
			//post to airtable api
			$data_to_post =  '{
				    "fields": {
				    "Name": "'.$name.'",
				    "Email": "'.$email.'",';
			if(!empty($phone)){
			    $data_to_post .= '"Phone Number": "'.$phone.'",';
			}
			if(!empty($message)){
			    $data_to_post .= '"Message": "'.$message.'"';
			}	    
			$data_to_post .= '
				}
			  }';
			  
			//print $data_to_post; 
			  
			$request_headers = array();
			$request_headers[] = 'Authorization: Bearer '. $API_KEY;
			$request_headers[] = 'Content-type: application/json';

			$curl = curl_init();
			curl_setopt($curl,CURLOPT_URL, $AIRTABLE_URL);
			curl_setopt($curl,CURLOPT_POST, sizeof($data_to_post));
			curl_setopt($curl,CURLOPT_POSTFIELDS, $data_to_post);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($curl);

			//print_r($result);

			curl_close($curl);
			
			$arr = json_decode($result, true);
			if ($arr['error']){
			    $emailmsg .= "ERROR sending data to Airtable.";
			} else {
			    $emailmsg .= "Data posted to Airtable.";
			}
		
		} else {
			print "Either Name or email missing.";
		}
	} else {
	    print "Incorrect CAPTCHA input.";
	    //$_SESSION['captcha'] = $_POST['cap'];
	}	
 }   
?>

 <!-- Start Nav Backed Header -->
  <div class="nav-backed-header parallax">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <ol class="breadcrumb">
            <li><a href="index.html">Home</a></li>
            <li class="active">Contact</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
  <!-- End Nav Backed Header --> 
  <!-- Start Page Header -->
  <div class="page-header">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <h1>{{ site.data.contact_page[0].Heading }}</h1>
        </div>
      </div>
    </div>
  </div>
  <!-- End Page Header --> 
  <!-- Start Content -->
  <div class="main" role="main">
    <div id="content" class="content full">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <header class="single-post-header clearfix">
              <h2 class="post-title">Contact Us</h2>
            </header>
            <div class="post-content">
              <div id="gmap">
                {{ site.data.location[0].Google_Maps_Embed_Code | raw }}
              </div>
              <div class="row">
                  {% if  site.data.contact_page[0].Content %}
                  {{ site.data.contact_page[0].Content | markdownify }}
                  {% endif %}
                   <div class="address">
            <p><i class="fa fa-map-marker"></i>  {{site.data.location[0].Street_Address}} {{site.data.location[0].Street_Address2}} {{site.data.location[0].City }}, {{site.data.location[0].State }} {{site.data.location[0].Zip }} <br>
                <i class="fa fa-phone"></i>  {{site.data.contact[0].Phone }} <br>
                <i class="fa fa-envelope"></i>  {{site.data.contact[0].Church_email }} 
                <hr>
              </div>
              <div class="row">
                <form method="post" id="contactform" name="contactform" class="contact-form" action="">
                <input type="hidden" id="cap" name="cap" value = '<?php print $_SESSION['captcha'];?>'/> 
                  <div class="col-md-6 margin-15">
                    <div class="form-group">
                      <input type="text" id="name" name="name"  class="form-control input-lg" placeholder="Name*" required aria-describedby="nameHelpText">
                    </div>
                    <div class="form-group">
                      <input type="email" id="email" name="email"  class="form-control input-lg" placeholder="Email*" required aria-describedby="emailHelpText">
                    </div>
                    <div class="form-group">
                      <input type="text" id="phone" name="phone" class="form-control input-lg" placeholder="Phone">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <textarea cols="6" rows="7" id="message" name="message" class="form-control input-lg" placeholder="Message" required></textarea>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <p>Please type the characters displayed below in the "Captcha" field.</p>
				            <img src = '<?php echo $_SESSION['captcha']['image_src']; ?>'>
                  </div><!--/col-md-6-->
                  <div class="col-md-6">
                    <label>Captcha
				<input type="text" id="captcha" name="captcha" required> 
			</label>
                  </div><!--/col-md-6-->
                  <div class="col-md-12">
                    <input  name="send" id="send" type="submit" class="btn btn-primary btn-lg pull-right" value="Send"/>
                  </div>
                </form>
                <div class="clearfix"></div>
                <div class="col-md-12">
                  <div id="message"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>