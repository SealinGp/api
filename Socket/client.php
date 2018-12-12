<?php
$msg = getopt("m:");
$msg = $msg['m'];
$msglength = 8192;
//-----发送消息....创建,连接,写 socket_create(AF_INET,SOCK_STREAM,SOL_TCP)
$sock = @socket_create(AF_INET,SOCK_STREAM,SOL_TCP) ? : false;
if (!$sock)exit("failed");
socket_connect($sock, '127.0.0.1', 1983);
socket_write($sock, $msg, strlen($msg));
echo "send msg success!\n";



//接收消息
do {
    $msg =   @socket_read($sock,$msglength);
}while(!$msg);
echo "read:$msg\n";
socket_close($sock);
exit;

