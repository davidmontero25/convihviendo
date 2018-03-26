
<?php

/////////////////Email///////////////////
$imapPath = '{tes.imap:993/imap/ssl}INBOX';
$username = 'username@example.com';
$password = 'passaword';
//////////////////Base de datos/////////////////////////

//////////////////Base de datos/////////////////////////
$dbhost = "localhost";
$dbport = 3306;
$dbusername = "dbuser";
$dbpassword = "dbpassaword";
$name = "name";
$conn = mysqli_connect($dbhost, $dbusername, $dbpassword, $name, $dbport);
mysqli_select_db($conn, $name);
mysqli_query($conn, "SET CHARACTER SET utf8");
$bind_marker = '?';
$_like_escape_chr = '!';

$inbox = imap_open($imapPath, $username, $password) or die('Cannot connect UO: ' . imap_last_error());
$emails = imap_search($inbox, 'SUBJECT "Nuevo mensaje 5355555054"');
$output = '';
foreach ($emails as $mail) {
    $headerInfo = imap_headerinfo($inbox, $mail);
    $from = $headerInfo->from[0]->mailbox . "@" . $headerInfo->from[0]->host;
    $to = $headerInfo->to[0]->mailbox . "@" . $headerInfo->to[0]->host;
    $structure = imap_fetchstructure($inbox, $mail);
    echo "From :" . $from . "<br>";
    echo "To:" . $to . "<br>";
    $content= _fetch($inbox, $mail);
    if($content!=''){
    $nummero = substr($content, 1, strpos($content, "-") - 1);
    $mensaje = trim(substr($content, strpos($content, ":") + 1));

    echo "Numero: " . $nummero;
    echo "<br>";
    echo "Mensaje:" . $mensaje . "<br>";
    $submens = substr($mensaje, strpos($mensaje, "Correo contacto:"));
    echo "Sun string :" . $submens . "<br>";
    if ($submens != $mensaje) {
        $from = str_replace("Correo contacto:", "", trim($submens));
    }
    $mensaje = str_replace($submens, "", trim($mensaje));
    $data = query($conn, "select consejero from cliente where tfono=" . $nummero);


    $consejero = NULL;
    if (count($data) != 0) {
        $consejero = $data[0]['consejero'];
    }
    echo "From Ultimo :" . $from . "<br>";
    $sql = "insert into entrada (email,texto,tfono,fecha,consejero)
        VALUES (" . "'" . $from . "'" . ", " . "'" . $mensaje . "'" . " , " . "'" . $nummero . "'" . " , " . "'" . date("Y-m-j H:M:s") . "'" . " ," . "'" . $consejero . "'" . ")";
    if (mysqli_query($conn, $sql)) {
        echo "New record created successfully" . "<br>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn) . "<br>";
    }
    //imap_delete($inbox, $mail);
    }
}
//imap_expunge($inbox);
imap_close($inbox);

function _decode($message, $coding) {
 /*switch ($coding) {
        case 2:
            $message = imap_binary($message);
            break;
        case 3:
            $message = imap_base64($message);
            break;
        case 4:
            $message = imap_qprint($message);
            break;
        case 5:
            break;
        default:
            break;
    }*/
    if(imap_base64($message)!=''){
        $message=imap_base64($message);
        return utf8_decode($message);
    }
     else{
        $message = imap_qprint($message);
        return utf8_decode($message);
     }


}
function query($conn, $sql, $params = array()) {
    $sql = bind($sql, $params);
    $rs = mysqli_query($conn, $sql);
    $data = array();
    while ($row = mysqli_fetch_array($rs)) {
        $data [] = $row;
    }
    return $data;
}

function bind($sql, $binds) {
    if (empty($binds) OR empty($bind_marker) OR strpos($sql, $bind_marker) === FALSE) {
        return $sql;
    } elseif (!is_array($binds)) {
        $binds = array($binds);
        $bind_count = 1;
    } else {
        // Make sure we're using numeric keys
        $binds = array_values($binds);
        $bind_count = count($binds);
    }

    // We'll need the marker length later
    $ml = strlen($bind_marker);

    // Make sure not to replace a chunk inside a string that happens to match the bind marker
    if ($c = preg_match_all("/'[^']*'/i", $sql, $matches)) {
        $c = preg_match_all('/' . preg_quote($bind_marker, '/') . '/i', str_replace($matches[0], str_replace($bind_marker, str_repeat(' ', $ml), $matches[0]), $sql, $c), $matches, PREG_OFFSET_CAPTURE);

        // Bind values' count must match the count of markers in the query
        if ($bind_count !== $c) {
            return $sql;
        }
    } elseif (($c = preg_match_all('/' . preg_quote($bind_marker, '/') . '/i', $sql, $matches, PREG_OFFSET_CAPTURE)) !== $bind_count) {
        return $sql;
    }

    do {
        $c--;
        $escaped_value = $this->escape($binds[$c]);
        if (is_array($escaped_value)) {
            $escaped_value = '(' . implode(',', $escaped_value) . ')';
        }
        $sql = substr_replace($sql, $escaped_value, $matches[0][$c][1], $ml);
    } while ($c !== 0);

    return $sql;
}

function insert($sql, $params = array()) {
    query($sql, $params);
    return mysql_insert_id($conn);
}

function _fetch($box, $mail) {
    $structure = imap_fetchstructure($box, $mail);
    $data = "";
     echo "Estructura".$structure->type;
    if ((!isset($structure->parts)) || (!is_array($structure->parts))) {
        $body = imap_body($box, $mail);

        $data = _decode($body, $structure->type);
        return $data;
    }
   /* else {
        $parts = _fetchPartsStructureRoot($mail, $structure);
        foreach ($parts as $part) {
            $content = new stdClass();
            $content->type = null;
            $content->data = null;
            $content->mime = _fetchType($part->data);
            if ((isset($part->data->disposition)) && ((strcmp('attachment', $part->data->disposition) == 0) || (strcmp('inline', $part->data->disposition) == 0))) {
                $content->type = $part->data->disposition;
                $content->name = null;
                if (isset($part->data->dparameters)) {
                    $content->name = _fetchParameter($part->data->dparameters, 'filename');
                }
                if (is_null($content->name)) {
                    if (isset($part->data->parameters)) {
                        $content->name = _fetchParameter($part->data->parameters, 'name');
                    }
                }
                $mail->attachments[] = $content;
            } else if ($part->data->type == 0) {
                $content->type = 'content';
                $content->charset = null;
            }
            $body = imap_fetchbody($box, $mail, $part->no);
            if (isset($part->data->encoding)) {
                $data = _decode($body, $part->data->encoding);
            } else {
                $data = $body;
            }
        }
    }*/
    return $data;
}

function _fetchPartsStructureRoot($mail, $structure) {
    $parts = array();
    if ((isset($structure->parts)) && (is_array($structure->parts)) && (count($structure->parts) > 0)) {
        foreach ($structure->parts as $key => $data) {
            _fetchPartsStructure($mail, $data, ($key + 1), $parts);
        }
    }
    return $parts;
}

function _fetchPartsStructure($mail, $structure, $prefix, &$parts) {
    if ((isset($structure->parts)) && (is_array($structure->parts)) && (count($structure->parts) > 0)) {
        foreach ($structure->parts as $key => $data) {
            $this->_fetchPartsStructure($mail, $data, $prefix . "." . ($key + 1), $parts);
        }
    }
    $part = new stdClass;
    $part->no = $prefix;
    $part->data = $structure;

    $parts[] = $part;
}

function _fetchType($structure) {
    $primary_mime_type = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");
    if ((isset($structure->subtype)) && ($structure->subtype) && (isset($structure->type))) {
        return $primary_mime_type[(int) $structure->type] . '/' . $structure->subtype;
    }
    return "TEXT/PLAIN";
}

