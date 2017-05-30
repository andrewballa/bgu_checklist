<?php
include 'global.php';


function checkPass() {
    return "13Saul56";
}

//get all contacts from Infusionsoft
function getContacts() {
  // ['LastName','FirstName', '_ProgramInterestedIn0', 'StageName', 'OwnerName', '_PaidAppFee', '_PersonalReference', '_PasterReferenceReceived',
  // '_TeacherEmployerReferenceReceived', '_HighSchoolTranscriptReceived', '_MostRecentCollegeTranscriptsReceived', '_CollegeTranscript2Received',
  // '_College3TranscriptsReceived', '_PhotoIDReceived','_AcademicAppealNeeded','_PaidRoomDeposit0', '_FilledOutRoommateQuestionnaire',
  // '_SentArrivalInformation0', '_FilledOutImmunizationForm0', '_FinalHighSchoolTranscriptsReceived','_FinalCollege1TranscriptReceived','_FinalCollege2TranscriptReceived',
  // '_FinalCollege3TranscriptReceived', '_FinancialAidStatus', _AppliedforFAFSA', '_CompletedVFAOStudentInterview', '_FinancialAidFinalized','_AppliedforStudentLoansoptional',
  // '_SentEmergencyContactInformation', '_RegisteredForClasses','_OnlineOrientationSeminarComplete', '_JoinedFacebook', '_AdditionalItemsNeeded', '_AdditionalItems', 'LeadID', 'StageID', 'OwnerID', 'Id'],

    $contactFields = array('Id','LastName','FirstName','_DateofAppStart','_ProgramInterestedIn0','OwnerID','_YearAppliedFor','_SemesterQuadAppliedFor','_PersonalInfoReceived','_PaidAppFee', '_PersonalReference', '_PasterReferenceReceived',
    '_TeacherEmployerReferenceReceived', '_HighSchoolTranscriptReceived', '_MostRecentCollegeTranscriptsReceived', '_CollegeTranscript2Received',
    '_College3TranscriptsReceived',  '_PreliminaryApplicationReceived','_PhotoIDReceived','_DateofApplicationComplete','_AcademicAppealNeeded','_PaidRoomDeposit0', '_FilledOutRoommateQuestionnaire',
    '_SentArrivalInformation0', '_FilledOutImmunizationForm0', '_FinalHighSchoolTranscriptsReceived','_FinalCollege1TranscriptReceived','_FinalCollege2TranscriptReceived',
    '_FinalCollege3TranscriptReceived', '_FinancialAidStatus', '_SentEmergencyContactInformation', '_RegisteredForClasses','_OnlineOrientationSeminarComplete', '_TitleIXComplete', '_JoinedFacebook', '_AdditionalItemsNeeded', '_AdditionalItems');

    $allDBContacts= recursiveFetchData("Contact",array('FirstName'=>'%'),$contactFields);
    return $allDBContacts;
}

function getStages($stageTye) {
    //ID's of the BGU Application stages (from Infusionsoft), only display students who are in these stages
    //refer to the file "test_data/allstages.json" to see what Stages these ID's belong to
    $stageIds = array('39','41','43','51','57','59','61','63','80','82','84','86','88','98','100','102','106','108','110','112','114','116','118','126');

    $call4 = buildXmlCall_query("Stage",1000,0,array('Id'=>'%'),array("Id","StageName","StageOrder"));
    $stages = executeApiCall($call4);

    $filterStages = null;

    if($stageTye == "all") {
      $filterStages = $stages;
    }
    else {
        foreach($stageIds as $id)
        {
            $keys = array_keys(array_column($stages,'Id'),$id);
            foreach($keys as $k)
            {
                $filterStages[] = $stages[$k];
            }
        }
    }
    return $filterStages;
}

function getUsers() {
    $users = recursiveFetchData("User",array("Id"=>"%"),array("Id", "FirstName","LastName"));
    return $users;
}

function createContactsArray() {
    $contacts = getContacts();
    $stages = getStages("limited");
    $users = getUsers();
    $leads = recursiveFetchData("Lead",array('Id'=>'%'),array("Id","StageID","ContactID","UserID"));

    //filter only the Leads that are in the Stages we want (these Stages are in getStages() function)
    $filteredLeads = null;
    foreach ($stages as $s)
    {
        $leadsInStage = array_keys(array_column($leads,'StageID'), $s['Id']);
        foreach ($leadsInStage as $l) {
            $filteredLeads[] = $leads[$l];
        }
    }

    //filter only the Contacts that are Leads in Infusionsoft
    $filteredContactArray = null;
    $contactIdList = array_column($filteredLeads,'ContactID');

    foreach ($contactIdList as $cId)
    {
        $e= array_search($cId,array_column($contacts,'Id'));
        $contactRecord = $contacts[$e];

        $r = array_search($cId,array_column($filteredLeads,'ContactID'));
        $lead = $filteredLeads[$r];
        $contactRecord += array("LeadID" => $lead['Id']);

        $j = array_search($lead['StageID'],array_column($stages,'Id'));
        $contactRecord += array("StageName" => $stages[$j]["StageName"]);
        $contactRecord += array("StageID" => $stages[$j]["Id"]);

        $n = array_search($contactRecord['OwnerID'], array_column($users, 'Id'));
        if($n!=""){
            $contactRecord+=array("OwnerName"=>$users[$n]['FirstName'] . " " . $users[$n]['LastName']);
        }


        $filteredContactArray[] = $contactRecord; //push this contact into the filtered array

    }

    return $filteredContactArray;
}

function updateContact() {

    global $key;
    $contactID= (int)$_REQUEST["Id"];
    $leadID = (int)$_REQUEST["LeadID"];
    $stageID = (int)$_REQUEST["StageID"];
    $ownerID = (int)$_REQUEST["OwnerID"];
    //save stage and owner

    $contactArray =  array(
        "FirstName" => $_REQUEST["FirstName"],"LastName" => $_REQUEST["LastName"],"_ProgramInterestedIn0" => $_REQUEST["_ProgramInterestedIn0"],"OwnerID" => $_REQUEST["OwnerID"],
        "_YearAppliedFor" => $_REQUEST["_YearAppliedFor"],"_SemesterQuadAppliedFor" => $_REQUEST["_SemesterQuadAppliedFor"],
        "_PersonalInfoReceived" => $_REQUEST["_PersonalInfoReceived"],"_PaidAppFee" => $_REQUEST["_PaidAppFee"],"_PersonalReference" => $_REQUEST["_PersonalReference"],"_PasterReferenceReceived" => $_REQUEST["_PasterReferenceReceived"],
        "_TeacherEmployerReferenceReceived" => $_REQUEST["_TeacherEmployerReferenceReceived"],"_HighSchoolTranscriptReceived" => $_REQUEST["_HighSchoolTranscriptReceived"],
        "_MostRecentCollegeTranscriptsReceived" => $_REQUEST["_MostRecentCollegeTranscriptsReceived"],"_CollegeTranscript2Received" => $_REQUEST["_CollegeTranscript2Received"],
        "_College3TranscriptsReceived" => $_REQUEST["_College3TranscriptsReceived"],"_PreliminaryApplicationReceived" => $REQUEST["_PreliminaryApplicationReceived"],"_PhotoIDReceived" => $_REQUEST["_PhotoIDReceived"],
        "_PaidRoomDeposit0" => $_REQUEST["_PaidRoomDeposit0"],"_AcademicAppealNeeded" => $_REQUEST["_AcademicAppealNeeded"],
        "_RegisteredForClasses" => $_REQUEST["_RegisteredForClasses"],"_OnlineOrientationSeminarComplete" => $_REQUEST["_OnlineOrientationSeminarComplete"],"_FilledOutRoommateQuestionnaire" => $_REQUEST["_FilledOutRoommateQuestionnaire"],"_SentArrivalInformation0" => $_REQUEST["_SentArrivalInformation0"],
        "_FinalHighSchoolTranscriptsReceived" => $_REQUEST["_FinalHighSchoolTranscriptsReceived"],
        "_FinalCollege1TranscriptReceived" => $_REQUEST["_FinalCollege1TranscriptReceived"],"_FinalCollege2TranscriptReceived" => $_REQUEST["_FinalCollege2TranscriptReceived"],
        "_FinalCollege3TranscriptReceived" => $_REQUEST["_FinalCollege3TranscriptReceived"],
        "_FinancialAidStatus" => $_REQUEST["_FinancialAidStatus"],"_JoinedFacebook" => $_REQUEST["_JoinedFacebook"],
        "_AdditionalItemsNeeded" => $_REQUEST["_AdditionalItemsNeeded"],"_AdditionalItems" => $_REQUEST["_AdditionalItems"],
    );
    //call to update contact record
    $call = new xmlrpcmsg("ContactService.update",array(
        php_xmlrpc_encode($key),
        php_xmlrpc_encode($contactID),
        php_xmlrpc_encode($contactArray),
    ));

    //Send the call
    $result_addcontact= executeApiCall($call);
    if($result_addcontact == $contactID) //if the contactId is returned, then no error
    {
        //call to update Lead record
        $call2 = buildXmlCall_DataUpdate("Lead",$leadID,array("StageID" => $stageID));
        $result = executeApiCall($call2);
        return $result;
    }
    else
    {
        return $result_addcontact;
    }
}


if(isset($_REQUEST['query'])) {

    if($_REQUEST['query']=="getContacts") {
        //$time_start = microtime(true);
        echo json_encode(createContactsArray());
        /*$s = getContacts();
        foreach($s as $c)
        {
            echo $c['FirstName']. ' ' . $c['LastName']. ' '. $c['OwnerID'] . 'stop<br>';
        }*/
        //$time_end = microtime(true);
        //echo $time_end - $time_start;
    }
    if($_REQUEST['query']=="getStages") {

        echo json_encode(getStages($_REQUEST['stageType']));
    }
    if($_REQUEST['query']=="getUsers") {
        echo json_encode(getUsers());
    }
    if($_REQUEST['query']=="saveContact") {

        $fname = updateContact();
        echo json_encode($fname);
        //echo "Saved Applicant: " . $fname;
    }
    if($_REQUEST['query']=="checkPass")
    {
        echo checkPass();
    }
}

//$time_start = microtime(true);
//$time_end = microtime(true);
//echo $time_end - $time_start;


?>
