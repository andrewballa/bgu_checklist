<?php
include("../xmlrpc-2.0/lib/xmlrpc.inc");
$client = new xmlrpc_client("https://hq171.infusionsoft.com/api/xmlrpc");
$client->return_type = "phpvals";
$client->setSSLVerifyPeer(FALSE);
$key = "1fb3245bda5f2517cf678c1cf28946a1";
//bgu 1fb3245bda5f2517cf678c1cf28946a1
//bethanygateway bc29d63e074cb34cceee0df381062c88 - passphrase abc123






?>