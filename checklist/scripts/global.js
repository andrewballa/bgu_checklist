function getRecordIndex(array,recordId)
{
    var n = 0;
    var result = $.grep(array, function(element,index) {
        if(element.Id == recordId)
        {
            n=index;
        }
    });
    return n;
}

function contactSave(record,vm) {
    /*['FirstName', 'LastName', 'StageName', '_ProgramInterestedIn0', 'OwnerName', '_PaidAppFee', '_PersonalReference',
        '_PasterReferenceReceived', '_HighSchoolTranscriptReceived', '_MostRecentCollegeTranscriptsReceived', '_CollegeTranscript2Received',
        '_College3TranscriptsReceived', '_PaidRoomDeposit0', '_EnrolledInClasses0', '_FilledoutPTQuestionnaire0', '_FilledOutRoommateQuestionnaire',
        '_SentArrivalInformation0', '_FilledOutImmunizationForm0', '_AppliedforFAFSA', '_CompletedVFAOStudentInterview', '_AppliedforStudentLoansoptional'
        , '_SentEmergencyContactInformation', '_JoinedFacebook', 'StageId', 'OwnerId', 'Id']*/
    var contact = {};
    var recordFields = Object.keys(record);
    console.log(recordFields)
    for(var j=0; j<recordFields.length ;j++)
    {
        var field = recordFields[j];
        contact.field = record[field];
    }

   /* contact.contactId = record.Id;
    contact.FirstName = $('#fname').val();
    contact.LastName= $('#lname').val();
    contact._ProgramInterestedIn0 = $('#prog').val();*/

    contact.query = "saveContact";

    var stageVal = $("#stage").val();

    if(stageVal!="unselected") {
        contact.StageId = stageVal;
        contact.StageName = $("#stage option:selected").text();
    }

    var ownerVal = $("#owner").val();
    if(ownerVal!="unselected") {
        contact.OwnerId = ownerVal;
        contact.OwnerName = $("#owner option:selected").text()
    }

    $.ajax({
        type: 'POST',
        url: 'api.php',
        data: "" //contact
    }).done(function (response) {
        swal({
            type: 'success',
            title: 'Saved Data',
            html: response
        }).done()

        var n = getRecordIndex(vm.gridData,record.Id)

        var contactFields = Object.keys(contact);

        //Vue.set(vm.gridData[n],"FirstName",data.FirstName)
        for(var i=0; i<contactFields.length ;i++)
        {
            var fieldName = contactFields[i]
            Vue.set(vm.gridData[n],fieldName,contact[fieldName])
        }

    }).fail(function (xhr,statusText,error) {
        swal({
            type:'error',
            title:'Something went wrong!',
            html:'Could not save data. <br> Error code : ' + xhr.status
        })
    })
}