<?php 
class WCMCA_Email
{
	public function __construct()
	{
	}
	public function get_email_ids()
	{
		$mailer = WC()->mailer( );
		$result = array();
		//wcmca_var_dump($mailer->get_emails());
		foreach($mailer->get_emails() as $email_type)
			if($email_type->is_customer_email( ))
			$result[] = $email_type->id;
			
		return $result;
	}
}
?>