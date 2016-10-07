<?php
include 'global.php';


function fetchStages()
{
    //ID's of the BGU Application stages (from Infusionsoft), only display students who are in these stages
    $stageIds = array('39','41','43','45','51','57','59','61','63','65','80','82','84','86','88','98','100','102','104','106','108','110' );

    $call4 = buildXmlCall_query("Stage",1000,0,array('Id'=>'%'),array("Id","StageName"));
    $stages = executeApiCall($call4);
}

function fetchContacts()
{
    //ID's of the BGU Application stages (from Infusionsoft), only display students who are in these stages
    $stageIds = array('39','41','43','45','51','57','59','61','63','65','80','82','84','86','88','98','100','102','104','106','108','110' );

    $contactFields = array('Id','FirstName','LastName','_ProgramInterestedIn0','OwnerID');

    /*$call2 = buildXmlCall_query("User",1000,0,array("Id"=>"%"),);
    $users = executeApiCall($call2);*/
    $users = recursiveFetchData("User",array("Id"=>"%"),array("Id", "FirstName","LastName"));

    $call4 = buildXmlCall_query("Stage",1000,0,array('Id'=>'%'),array("Id","StageName"));
    $stages = executeApiCall($call4);

    $leads = recursiveFetchData("Lead",array('Id'=>'%'),array("Id","StageID","ContactID","UserID"));

    //only get Leads that are in the Stages we want (stages that correspond to $stageIds)
    $filterLeads = null;
    foreach ($stageIds as $sid)
    {
        $leadsInStage = array_keys(array_column($leads,'StageID'), $sid);
        foreach ($leadsInStage as $match) {
            $filterLeads[] = $leads[$match];
        }
    }

    //get all contacts from Infusionsoft
    $allDBContacts= recursiveFetchData("Contact",array('Id'=>'%'),$contactFields);

    //only get Contacts that are Leads in Infusionsoft
    $filterContacts = null;
    foreach ($allDBContacts as $c)
    {
        $leadsOfContact = array_keys(array_column($filterLeads,'ContactID'),$c[Id]);
        foreach ($leadsOfContact as $match)
        {
            $contactArray = $allDBContacts[$match];
            $i = array_search($c[Id],array_column($filterLeads,'ContactID'));
            $j = array_search($filterLeads[$i][StageID],array_column($stages,'Id'));
            $n = array_search($contactArray[OwnerID], array_column($users, 'Id'));

            $contactArray+=array("StageName"=>$stages[$j]["StageName"]);
            $contactArray+=array("StageId"=>$stages[$j]["Id"]);
            $contactArray+=array("OwnerName"=>$users[$n][FirstName] . " " . $users[$n][LastName]);
            $contactArray+=array("OwnerId"=>$users[$n][Id]);

            $filterContacts[] = $contactArray; //push this contact into the filtered array
        }
    }

    //sort contacts by last name
    usort($filterContacts, function($a, $b) {
        return strcmp($a["LastName"], $b["LastName"]);
    });

    echo $filterContacts;
}

function addUpdateContacts()
{
    global $key;

    $array_contact = "";
    $duplicateCheckField = "Email";
    $call = new xmlrpcmsg("ContactService.addWithDupCheck",array(
        php_xmlrpc_encode($key),
        php_xmlrpc_encode($array_contact),
        php_xmlrpc_encode($duplicateCheckField),
    ));

    //Send the call
    $result_addcontact= executeApiCall($call);

    if($result_addcontact!="ERROR") {
        echo "ContactId: " . $result_addcontact;
        return $result_addcontact;
    }
    else {
        echo $result_addcontact->faultString();
        return null;
    }
}

fetchContacts();

?>