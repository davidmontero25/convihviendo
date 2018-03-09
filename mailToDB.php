<?php

include './IMAP/imap.php';
include './DB/MySQL.php';

$box = new ImapReader('imappro.zoho.com', '993', 'user', 'pass');
$mysql = new MySQL("conviviendo", "localhost", 3306, "root", "");
$box->connect()->fetchSearchHeaders("", 'SUBJECT "Importante !"');

for ($i = 0; ( $i < $box->count()); $i++) {
    $msg = $box->get($i);
    print_r($msg);
    $subject = $msg->subject;

    $from = $msg->from[0]->mailbox . "@" . $msg->from[0]->host;
    $to = $msg->to[0]->mailbox . "@" . $msg->to[0]->host;
    $msg = $box->fetch($msg);
    $content = $msg->content[0]->data;
    $nummero = substr($content, 1, strpos($content, "-") - 1);
    $mensaje = trim(substr($content, strpos($content, ":") + 1));
    echo "From :" . $from . "<br>";
    echo "To:" . $to . "<br>";
    echo "Numero: " . $nummero;
    echo "<br>";
    echo "Mensaje" . $mensaje;

    $submens = substr($mensaje, strpos($mensaje, "Correo contacto:"));
    if ($submens != $mensaje) {
        $from = str_replace("Correo contacto:", "", trim($from));
    }
    $box->delete($mesg);
    $data = $mysql->query("select consejero from cliente where tfono=" . $nummero);

    $consejero = NULL;
    if (count($data) != 0) {
        $consejero = $data[0]['consejero'];
    }
    $id = $mysql->insert("insert into entrada (email,texto,tfono,fecha,consejero)values(?,?,?,?,?)", [$from, $mensaje, $nummero, date("Y-m-j H:M:s"), $consejero]);
}
