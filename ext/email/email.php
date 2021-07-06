<?php
/**
 * Created by PhpStorm.
 * User: sea
 * Date: 2018/9/9
 * Time: 10:09
 */
include_once 'autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class email
{
    public $host   = 'smtp.163.com';
    public $port   = 25;
    public $sender = [
        'name'    => 'sealingp',
        'email'   => 'sealingp@163.com',
        'pass'    => '19941126Zx',
        'port'    => 25,
    ];
    public function send(string $title,string $content,string $receiver_email):string
    {
        
        /*$email           = new PHPMailer(true);    //得到一个PHPMailer实例
        $email->CharSet  = "UTF-8";                //设置采用utf-8中文编码(内容不会乱码)
        $email->isSMTP();                          //设置采用SMTP方式发送邮件
        $email->Host     = $this->host;            //设置邮件服务器的地址(若为163邮箱，则是smtp.163.com)
        $email->Port     = $this->port;            //设置端口
        $email->From     = $this->sender['email'];  //发件人
        $email->FromName = $this->sender['name'];  //发送人姓名
        $email->SMTPAuth = true;                   //设置SMTP是否需要密码验证，true表示需要
        $email->Username = $this->sender['email']; //发件人账号
        $email->Password = $this->sender['pass'];  //发件人密码
        $email->Subject  = $title;                 //标题
        $email->AltBody  = "text/html";            //格式
        $email->Body     = $content;               //内容
        $email->IsHTML(true);                      //设置每行的字符数
        $email->WordWrap = 50;                     //设置回复的收件人的地址(from可随意)
        $email->AddReplyTo($this->sender['email'], "from");//设置收件的地址(to可随意)
        $email->AddAddress($receiver_email, "to");
        $bool = $email->Send();*/
        
        $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
        try {
            //Server settings
            $mail->SMTPDebug = 0;                                 // Enable verbose debug output,是否输出debug信息(执行步骤等信息)
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = $this->host;                            // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = $this->sender['email'];             // SMTP username
            $mail->Password = $this->sender['pass'];              // SMTP password
            $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $this->sender['port'];                  // TCP port to connect to
        
            //Recipients
            $mail->setFrom($this->sender['email'], $this->sender['name']);
            $mail->addAddress($receiver_email);     // Add a recipient
            /*$mail->addAddress('ellen@example.com');               // Name is optional*/
            $mail->addReplyTo($this->sender['email'], $this->sender['name']);
            /*$mail->addCC('cc@example.com');
            $mail->addBCC('bcc@example.com');*/
        
            //Attachments
            /*$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name*/
        
            //Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $title;
            $mail->Body    = $content;
//            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
            $mail->send();
            return 'success';
        } catch (Exception $e) {
            return $mail->ErrorInfo;
        }
        
        
    }
}