<?php

class Test extends CI_Controller {
	
	public function test2() {
		echo "UUID: " . generateUUID();
	}
	
	public function email() {
		$config = array(
    		'protocol' => 'smtp',
    		'smtp_host' => 'venus.jogjahost.com', 
    		'smtp_port' => 465,
    		'smtp_user' => 'admin@ensido.com',
    		'smtp_pass' => 'HelloWorld@123',
    		'mailtype' => 'text',
    		'smtp_protocol' => 'ssl',
    		'smtp_timeout' => '4', 
    		'charset' => 'iso-8859-1',
    		'wordwrap' => TRUE
		);
		$this->load->library('email', $config);
        $this->email->set_newline("\r\n");
        $this->email->from("admin@ensido.com");
        $this->email->to("danaoscompany@gmail.com");
        $this->email->subject("This is subject");
        $this->email->message("This is message");
        if ($this->email->send()) 
		{
            echo 'Email has been sent successfully';
        } 
		else 
		{
            show_error($this->email->print_debugger());
        }
	}
	
	public function email2() {
		$to      = 'danaoscompany@gmail.com';
		$subject = 'the subject';
		$message = 'hello';
		$headers = 'From: admin@ensido.com' . "\r\n" .
    		'Reply-To: admin@ensido.com' . "\r\n" .
    		'X-Mailer: PHP/' . phpversion();
		mail($to, $subject, $message, $headers);
	}
	
	public function file() {
		file_put_contents("test.txt", "Halo dunia");
	}
}
