<?php
include 'global.php';


function checkPass()
{
    return "monkey";
}

//get all contacts from Infusionsoft
function getContacts()
{
    $contactFields = array('Id','FirstName','LastName','_ProgramInterestedIn0','OwnerID','_PaidAppFee','_PersonalReference','_PasterReferenceReceived',
        '_HighSchoolTranscriptReceived','_MostRecentCollegeTranscriptsReceived','_CollegeTranscript2Received','_College3TranscriptsReceived','_PaidRoomDeposit0',
        '_EnrolledInClasses0','_FilledoutPTQuestionnaire0','_FilledOutRoommateQuestionnaire','_SentArrivalInformation0','_FilledOutImmunizationForm0',
        '_AppliedforFAFSA','_CompletedVFAOStudentInterview','_AppliedforStudentLoansoptional','_SentEmergencyContactInformation','_JoinedFacebook');
    $allDBContacts= recursiveFetchData("Contact",array('FirstName'=>'%'),$contactFields);
    return $allDBContacts;
}

function getStages()
{
    //ID's of the BGU Application stages (from Infusionsoft), only display students who are in these stages
    $stageIds = array('39','41','43','45','51','57','59','61','63','65','80','82','84','86','88','98','100','102','104','106','108','110' );

    $call4 = buildXmlCall_query("Stage",1000,0,array('Id'=>'%'),array("Id","StageName"));
    $stages = executeApiCall($call4);

    $filterStages = null;
    foreach($stageIds as $id)
    {
        $keys = array_keys(array_column($stages,'Id'),$id);
        foreach($keys as $k)
        {
            $filterStages[] = $stages[$k];
        }
    }

    usort($filterStages, function($a, $b) {
        //if($a["LastName"]!=null & $b["LastName"]!=null) {
        return strcmp($a['StageName'], $b['StageName']);
        //}
    });

    return $filterStages;
}

function getUsers()
{
    $users = recursiveFetchData("User",array("Id"=>"%"),array("Id", "FirstName","LastName"));
    return $users;
}

function createContactsArray()
{
    $contacts = getContacts();
    $stages = getStages();
    $users = getUsers();
    $leads = recursiveFetchData("Lead",array('Id'=>'%'),array("Id","StageID","ContactID","UserID"));

    //filter only the Leads that are in the Stages we want (stages that correspond to $stageIds)
    $filteredLeads = null;
    foreach ($stages as $s)
    {
        $leadsInStage = array_keys(array_column($leads,'StageID'), $s['Id']);
        foreach ($leadsInStage as $l) {
            $filteredLeads[] = $leads[$l];
        }
    }

    //echo "all cons" . count($allDBContacts) . '<br>';
    //filter only the Contacts that are Leads in Infusionsoft
    $filteredContactArray = null;
    $cIdArray = array_column($filteredLeads,'ContactID');

    foreach ($cIdArray as $cId)
    {
        $e= array_search($cId,array_column($contacts,'Id'));
        $contactRecord = $contacts[$e];

        $r = array_search($cId,array_column($filteredLeads,'ContactID'));
        $lead = $filteredLeads[$r];

        $j = array_search($lead['StageID'],array_column($stages,'Id'));
        $contactRecord+=array("StageName"=>$stages[$j]["StageName"]);
        $contactRecord+=array("StageId"=>$stages[$j]["Id"]);


        $n = array_search($contactRecord['OwnerID'], array_column($users, 'Id'));
        $contactRecord+=array("OwnerName"=>$users[$n]['FirstName'] . " " . $users[$n]['LastName']);
        $contactRecord+=array("OwnerId"=>$contactRecord['OwnerID']);

        $filteredContactArray[] = $contactRecord; //push this contact into the filtered array

    }

    //echo "filtered cons" . count($filteredContacts) . '<br>';

    //sort contacts by last name
        usort($filteredContactArray, function($a, $b) {
            return strcmp($a['LastName'], $b['LastName']);
        });

    return $filteredContactArray;
}

function updateContact()
{
    /*['FirstName', 'LastName', 'StageName', '_ProgramInterestedIn0', 'OwnerName', '_PaidAppFee', '_PersonalReference',
        '_PasterReferenceReceived', '_HighSchoolTranscriptReceived', '_MostRecentCollegeTranscriptsReceived', '_CollegeTranscript2Received',
        '_College3TranscriptsReceived', '_PaidRoomDeposit0', '_EnrolledInClasses0', '_FilledoutPTQuestionnaire0', '_FilledOutRoommateQuestionnaire',
        '_SentArrivalInformation0', '_FilledOutImmunizationForm0', '_AppliedforFAFSA', '_CompletedVFAOStudentInterview', '_AppliedforStudentLoansoptional'
        , '_SentEmergencyContactInformation', '_JoinedFacebook', 'StageId', 'OwnerId', 'Id']*/
    global $key;
    $contactId= $_REQUEST["contactId"];

    $contactArray = null;

    $post= $_POST;
    $test = "";
    foreach($post as $key => $value)
    {
        $test .= "{$key}: {$value} ";
    }
    return $test;

/*    $call = new xmlrpcmsg("ContactService.update",array(
        php_xmlrpc_encode($key),
        php_xmlrpc_encode($contactId),
        php_xmlrpc_encode($contactArray),
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
    }*/
}

//$time_start = microtime(true);
//$time_end = microtime(true);
//echo $time_end - $time_start;


if(isset($_REQUEST['query']))
{
    if($_REQUEST['query']=="getContacts") {
        //$time_start = microtime(true);
        echo json_encode(createContactsArray());
        //$time_end = microtime(true);
        //echo $time_end - $time_start;
    }
    if($_REQUEST['query']=="getStages") {
        echo json_encode(getStages());
    }
    if($_REQUEST['query']=="getUsers") {
        echo json_encode(getUsers());
    }
    if($_REQUEST['query']=="saveContact") {

        $fname = updateContact();
        echo "Saved Applicant: " . $fname;
    }
    if($_REQUEST['query']=="checkPass")
    {
        echo checkPass();
    }

}

?>
