<?php
include("../xmlrpc-2.0/lib/xmlrpc.inc");
$client = new xmlrpc_client("https://hq171.infusionsoft.com/api/xmlrpc");
$client->return_type = "phpvals";
$client->setSSLVerifyPeer(FALSE);
$key = "1fb3245bda5f2517cf678c1cf28946a1";
//bgu 1fb3245bda5f2517cf678c1cf28946a1
//bethanygateway bc29d63e074cb34cceee0df381062c88 - passphrase abc123

##################################################
###     FUNCTIONS TO EXECUTE XML API CALLS     ###
##################################################

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

function buildXmlCall_Add($tableName,$struct_itemsToAdd)
{
    global $key;

    $call = new xmlrpcmsg("DataService.add", array(
        php_xmlrpc_encode($key),
        php_xmlrpc_encode($tableName), //which table to add too
        php_xmlrpc_encode($struct_itemsToAdd),
    ));

    return $call;
}

function buildXmlCall_Update($tableName, $rowId, $struct_itemsToUpdate)
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

function executeApiCall($xmlCall)
{
    global $client;
    //Send the call
    $result=$client->send($xmlCall);

    if(!$result->faultCode()) {
        return $result->value();
    }
    else if($result->faultCode()) {
        /*//if there's an error, write the error message and the xmlcall to a log file
        $vardump = var_export(php_xmlrpc_decode($xmlCall), true);
        $filecontents = "INFUSIONSOFT API ERROR MESSAGE: " . date("Y-m-d H:i:s") . "\r\n". $result->faultString() . "\r\n\r\n" . $xmlCall->method() . "\r\n\r\n" . $vardump;
        createErrorLog($filecontents);
        return "ERROR";*/
        echo $result->faultString() . "\r\n\r\n" . $xmlCall->method();//remove
    }
    else
    {
        /*$vardump = var_export(php_xmlrpc_decode($xmlCall), true);
        $filecontents = "ERROR: " . date("Y-m-d H:i:s") . "\r\n". $result->faultString() . "\r\n\r\n" . $xmlCall->method() . "\r\n\r\n" . $vardump;
        createErrorLog($filecontents);*/
    }
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

function addUpdateContacts()
{

    /*$duplicateCheckField = "Email";
    $call2 = new xmlrpcmsg("ContactService.addWithDupCheck",array(
        php_xmlrpc_encode($key),
        php_xmlrpc_encode($array_contact),
        php_xmlrpc_encode($duplicateCheckField),
    ));*/
}

function fetchContacts()
{
    //ID's of the BGU Application stages (from Infusionsoft), only display students who are in these stages
    $stageIds = array('39','41','43','45','51','57','59','61','63','65','80','82','84','86','88','98','100','102','104','106','108','110' );

    $contactFields = array('Id','FirstName','LastName','_ProgramInterestedIn0','OwnerID');

    $call2 = buildXmlCall_query("User",1000,0,array("Id"=>"%"),array("Id", "FirstName","LastName"));
    $users = executeApiCall($call2);

    $call4 = buildXmlCall_query("Stage",1000,0,array('Id'=>'%'),array("Id","StageName"));
    $stages = executeApiCall($call4);

    $leads = recursiveFetchData("Lead",array('Id'=>'%'),array("Id","StageID","ContactID","UserID"));
    $filterLeads = null;
    foreach ($stageIds as $sid)
    {
        $stageKeys = array_keys(array_column($leads,'StageID'), $sid);
        foreach ($stageKeys as $k) {
            $filterLeads[] = $leads[$k];
        }
    }

    $allDBContacts= recursiveFetchData("Contact",array('Id'=>'%'),$contactFields);
    $filterContacts = null;
    foreach ($allDBContacts as $c)
    {
        $leadKeys = array_keys(array_column($filterLeads,'ContactID'),$c[Id]);
        foreach ($leadKeys as $k)
        {
            $contactArray = $allDBContacts[$k];
            $i = array_search($c[Id],array_column($filterLeads,'ContactID'));
            $j = array_search($filterLeads[$i][StageID],array_column($stages,'Id'));
            $n = array_search($contactArray[OwnerID], array_column($users, 'Id'));

            $contactArray+=array("StageName"=>$stages[$j]["StageName"]);
            $contactArray+=array("OwnerName"=>$users[$n][FirstName] . " " . $users[$n][LastName]);

            $filterContacts[] = $contactArray; //push this contact into the filtered array
        }
    }
    usort($filterContacts, function($a, $b) {
        return strcmp($a["LastName"], $b["LastName"]);
    });

    return $filterContacts;
}


if($_SERVER['REQUEST_METHOD'] == 'POST' )
{
    $fname = $_POST["fname"];
    //echo $fname;
}


?>


<link rel="stylesheet" href="style.css" type="text/css" media="screen" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/sweetalert2/5.1.1/sweetalert2.min.css" type="text/css"/>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/1.0.26/vue.min.js"></script>
<script src="https://cdn.jsdelivr.net/sweetalert2/5.1.1/sweetalert2.min.js"></script>
<script src="scripts.js"></script>


<div id="app">
    Search: <input v-model="searchQuery">
    <table>
        <thead>
        <tr>
            <th v-for="field in fields" @click="sortBy(field)"  :class="{active: sortField == field}">
                {{ headerNames[$index] }}
                <span class="arrow" :class="order > 0 ? 'asc' : 'dsc'"></span>
            </th>
            <th>Edit</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="record of gridData | filterBy searchQuery | orderBy sortField order">
            <td v-for="field in fields" :class="{idCell: field=='Id'}" :data-ownerid="field=='OwnerName' ? record[field] : ''">
                {{record[field]}}
            </td>
            <td class="editBtn" @click="editRow(record)">Edit</td>
        </tr>
        </tbody>
    </table>
</div>




<script>
    //var contacts = <?php /*echo json_encode(fetchContacts());*/ ?>

    // bootstrap the demo
    var demo = new Vue({
        el: '#app',
        data: {
            order: 1,
            sortField: 'Id',
            searchQuery: '',
            fields: ['Id', 'FirstName','LastName','StageName','_ProgramInterestedIn0','OwnerName'],
            gridData: null,
            headerNames: ['Id', 'First Name','Last Name','Stage','Program','Owner']
        },
        ready: function () {
            var that = this;
            $.get( "./contact.json", function(data){
                that.gridData = data;
            });
        },
        methods: {
            sortBy: function (column) {
                this.sortField = column
                this.order = this.order * -1
            },
            editRow: function (recordId){
                //console.log(recordId);
                var record = recordId;//this.gridData[recordId]; added comment again

                var fname = record.FirstName!=undefined?record.FirstName:"";
                var lname = record.LastName!=undefined?record.LastName:"";
                var stage = record.StageName!=undefined?record.StageName:"";
                var prog = record._ProgramInterestedIn0!=undefined?record._ProgramInterestedIn0:"";
                var owner = record.OwnerName!=undefined?record.OwnerName:"";

                //sweet alert modal, which handles the edit form and ajax request to save data
                swal({
                    title: 'Edit Applicant',
                    html:
                    '<form id="editForm" method="post" action="gridview.php">' +
                    '<div class="field"><label for="fname">First Name</label><input type="text" id="fname" value="'+ fname + '"></div>' +
                    '<div class="field"><label for="lname">Last Name</label><input type="text" id="lname" value="'+ lname + '"></div>' +
                    '<div class="field"><label for="stage">Stage</label><input type="text" id="stage" value="'+ stage + '"></div>' +
                    '<div class="field"><label for="prog">Program</label><input type="text" id="prog" value="'+ prog + '"></div>' +
                    '<div class="field"><label for="owner">Owner</label><input type="text" id="owner" value="'+ owner + '"></div>' +
                    '</form>',
                    showCloseButton: true,
                    showCancelButton: true,
                    confirmButtonText:'Save',
                    cancelButtonText:'Cancel',
                    allowOutsideClick:false,
                    allowEscapeKey:false,
                    showLoaderOnConfirm: true,
                    preConfirm:function () { //when confirm button is pressed
                        return new Promise(function (resolve,reject) {
                            var data = {};
                            data.fname = $('#fname').val();
                            data.lname= $('#lname').val();
                            data.stage = $('#stage').val();
                            data.prog = $('#prog').val();
                            data.owner = $('#owner').val();
                            $.ajax({
                                type: 'POST',
                                url: 'api.php',
                                data: data
                            }).done(function (response,statusText,xhr) {
                                swal({
                                    type: 'success',
                                    title: 'Saved Data',
                                    html: response
                                })
                            }).fail(function (xhr,statusText,error) {
                                reject("Error - error code:" + xhr.status);
                            })
                        })
                    }
                })
            }
        }
    })


</script>
