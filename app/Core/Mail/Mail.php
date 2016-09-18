<?php
namespace App\Core\Mail;
use Exception;

class Mail
{
    private $mail = null;
    public function __construct()
    {
        $this->mail = new \PHPMailer;

        //$mail->SMTPDebug = 3;                               // Enable verbose debug output

        $this->mail->isSMTP();                                      // Set mailer to use SMTP
        $this->mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
        $this->mail->SMTPAuth = true;                               // Enable SMTP authentication
        $this->mail->Username = 'jose.delgado@yaxa.co';                 // SMTP username
        $this->mail->Password = '1234qwas$$';                           // SMTP password
        $this->mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $this->mail->Port = 587;                                    // TCP port to connect to

        // $this->mail->setFrom('command@commands.com', 'Yaxa Commands');
        // $this->mail->addAddress('jose.delgado@yaxa.co', 'Jose Angel');     // Add a recipient
        // $this->mail->addAddress('ellen@example.com');               // Name is optional
        // $this->mail->addReplyTo('info@example.com', 'Information');
        // $this->mail->addCC('cc@example.com');
        // $this->mail->addBCC('bcc@example.com');

        // $this->mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        // $this->mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        $this->mail->isHTML(true);                                  // Set email format to HTML

        // $this->mail->Subject = 'Here is the subject';
        // $this->mail->Body    = 'This is the HTML message body <b>in bold!</b>';

    }
    public function Send()
    {
        if (!$this->mail->send()) {
            throw new Exception('Mailer Error: ' . $this->mail->ErrorInfo, 1);
        }
        return true;
    }
    public function From($email,$name=null) {
        $this->mail->setFrom($email,$name);
    }

    public function To($email,$name=null) {
        $this->mail->addAddress($email,$name);
    }
    public function Subject($value='') {
        $this->mail->Subject = $value;
    }
    public function Body($value='') {
        $this->mail->Body = $value;
    }
    
    public function __set($key,$value)
    {
        $this->mail->{$key} = $value;
    }
    
}
 