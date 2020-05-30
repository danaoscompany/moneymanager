<?php

class Test extends CI_Controller {
	
	public function test2() {
		echo "Hello";
	}
	
	public function email() {
		$mail_config['smtp_host'] = 'ssl://smtp.gmail.com';
$mail_config['smtp_port'] = '465';
$mail_config['smtp_user'] = 'danaos.apps@gmail.com';
$mail_config['_smtp_auth'] = TRUE;
$mail_config['smtp_pass'] = 'PublicVoid123';
$mail_config['smtp_crypto'] = 'tls';
$mail_config['protocol'] = 'smtp';
$mail_config['mailtype'] = 'html';
$mail_config['send_multipart'] = FALSE;
$mail_config['charset'] = 'utf-8';
$mail_config['wordwrap'] = TRUE;
$this->email->initialize($mail_config);

$this->email->set_newline("\r\n");
	    $this->load->library('email', $mail_config);
      $this->email->from('danaos.apps@gmail.com'); // change it to yours
      $this->email->to('danaoscompany@gmail.com');// change it to yours
      $this->email->subject('This is subject');
      $this->email->message('This is message');
      if($this->email->send())
     	{
      	echo 'Email sent.';
     	}
     	else
   		{
     	show_error($this->email->print_debugger());
    	}
	}
}
