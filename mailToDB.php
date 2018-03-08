<?php

include './IMAP/imap.php';
include './DB/MySQL.php';

//$box = new ImapReader('imappro.zoho.com', '993', 'consejeria@convihviendo.com', 'Consejos');
$box = new ImapReader('imap.uo.edu.cu', '993', 'david.montero@uo.edu.cu', 'Mcrespo0302++');
$mysql = new MySQL("conviviendo","localhost",3306,"root","");
$box->connect()->fetchSearchHeaders("",'SUBJECT "Nuevo mensaje 5355555054"');

for ($i = 0; ( $i < $box->count()); $i++) {
    $msg = $box->get($i);
    //
    $subject = $msg->subject;    

	$from = $msg->from[0]->mailbox . "@" . $msg->from[0]->host;
	$to = $msg->to[0]->mailbox . "@" . $msg->to[0]->host;
    $msg = $box->fetch($msg);
    $content= $msg->content[0]->data ;
    $nummero=substr($content, 1, strpos($content, "-")-1);
    $mensaje=trim(substr($content, strpos($content, ":")+1));
    echo "From :" . $from . "<br>";
    echo "To:" . $to . "<br>";
    echo "Numero: " .$nummero ;
    echo "<br>";
    echo "Mensaje" . $mensaje;      
	$box->delete($mesg);		
    $data = $mysql->query("select consejero from cliente where tfono=".$nummero);
//echo($data[0]['consejero']);
        $consejero = NULL;
        if (count($data) != 0) {
            $consejero = $data[0]['consejero'];
        }
        $id = $mysql->insert("insert into entrada (email,texto,tfono,fecha,consejero)values(?,?,?,?,?)", [$from,$mensaje,$nummero,date("Y-m-j H:M:s"), $consejero]);
  
}
