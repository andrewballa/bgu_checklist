<?php
include("xmlrpc-2.0/lib/xmlrpc.inc");

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$client = new xmlrpc_client("https://hq171.infusionsoft.com/api/xmlrpc");
$client->return_type = "phpvals";
$client->setSSLVerifyPeer(FALSE);
$key = "1fb3245bda5f2517cf678c1cf28946a1";
//bgu 1fb3245bda5f2517cf678c1cf28946a1
//bethanygateway bc29d63e074cb34cceee0df381062c88 - passphrase abc123

##################################################
###     FUNCTIONS TO EXECUTE XML API CALLS     ###
##################################################

function executeApiCall($xmlCall)
{
    global $client;
    //Send the call
    $result=$client->send($xmlCall);

    if(!$result->faultCode()) {
        return $result->value();
    }
    else if($result->faultCode()) {
        //if there's an error, write the error message and the xmlcall to a log file
        $vardump = var_export(php_xmlrpc_decode($xmlCall), true);
        $filecontents = "INFUSIONSOFT API ERROR MESSAGE: " . date("Y-m-d H:i:s") . "\r\n". $result->faultString() . "\r\n\r\n" . $xmlCall->method() . "\r\n\r\n" . $vardump;
        createErrorLog($filecontents);
        return "ERROR";
    }
    else
    {
        $vardump = var_export(php_xmlrpc_decode($xmlCall), true);
        $filecontents = "ERROR: " . date("Y-m-d H:i:s") . "\r\n". $result->faultString() . "\r\n\r\n" . $xmlCall->method() . "\r\n\r\n" . $vardump;
        createErrorLog($filecontents);
        return "ERROR";
    }
}

function buildXmlCall_query($tableName, $howManyRecords, $pageToReturn, $struct_SearchFields, $array_FieldsToReturn)
{
    global $key;

    //api call to find product, returns an Array
    $call = new xmlrpcmsg("DataService.query",array(
        php_xmlrpc_encode($key),
        php_xmlrpc_encode($tableName), //which table to find from
        php_xmlrpc_encode($howManyRecords), //how many records
        php_xmlrpc_encode($pageToReturn), //which page to retrieve, 0 is default
        php_xmlrpc_encode($struct_SearchFields), //the field(s) to search on
        php_xmlrpc_encode($array_FieldsToReturn), //the fields to return
    ));
    return $call;
}

function buildXmlCall_DataAdd($tableName, $struct_itemsToAdd)
{
    global $key;

    $call = new xmlrpcmsg("DataService.add", array(
        php_xmlrpc_encode($key),
        php_xmlrpc_encode($tableName), //which table to add too
        php_xmlrpc_encode($struct_itemsToAdd),
    ));

    return $call;
}

function buildXmlCall_DataUpdate($tableName, $rowId, $struct_itemsToUpdate)
{
    global $key;

    $call = new xmlrpcmsg("DataService.update", array(
        php_xmlrpc_encode($key),
        php_xmlrpc_encode($tableName), //which table to update
        php_xmlrpc_encode($rowId), //ID of row to update
        php_xmlrpc_encode($struct_itemsToUpdate),
    ));

    return $call;
}

function createErrorLog($filecontents)
{
    $errorfile = fopen("errorlog/error_log_".date("Y-m-d H:i:s").".txt", "w") or die("Unable to open file!");
    fwrite($errorfile, $filecontents);
    fclose($errorfile);
}

function recursiveFetchData($table,$struct_SearchFields,$array_FieldsToReturn)
{
    $page = 0;
    //$all_records = null;

    while(true)
    {
        $call = buildXmlCall_query($table,1000,$page,$struct_SearchFields,$array_FieldsToReturn);
        $records = executeApiCall($call);
        //$all_records[] = $records;

        foreach ($records as $v) { //append all elements of current array to main array
            $all_records[] = $v;
        }

        if(count($records) < 1000)
        {
            break;
        }
        $page++;
    }
    return $all_records;
}



?>