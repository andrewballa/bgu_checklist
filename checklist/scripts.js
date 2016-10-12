function contactSave(record,vm) {
    /*['FirstName', 'LastName', 'StageName', '_ProgramInterestedIn0', 'OwnerName', '_PaidAppFee', '_PersonalReference',
        '_PasterReferenceReceived', '_HighSchoolTranscriptReceived', '_MostRecentCollegeTranscriptsReceived', '_CollegeTranscript2Received',
        '_College3TranscriptsReceived', '_PaidRoomDeposit0', '_EnrolledInClasses0', '_FilledoutPTQuestionnaire0', '_FilledOutRoommateQuestionnaire',
        '_SentArrivalInformation0', '_FilledOutImmunizationForm0', '_AppliedforFAFSA', '_CompletedVFAOStudentInterview', '_AppliedforStudentLoansoptional'
        , '_SentEmergencyContactInformation', '_JoinedFacebook', 'StageId', 'OwnerId', 'Id']*/
    var contact = {};
    contact.contactId = record.Id;
    contact.FirstName = $('#fname').val();
    contact.LastName= $('#lname').val();
    contact._ProgramInterestedIn0 = $('#prog').val();

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
        data: contact
    }).done(function (response) {
        swal({
            type: 'success',
            title: 'Saved Data',
            html: response
        }).done()

        var n = 0;
        var result = $.grep(vm.gridData, function(element,index) {
            if(element.Id == record.Id)
            {
                n=index;
            }
        });

        var contactFields = Object.keys(contact);
        //console.log(contactFields)

        for(var i=0; i<contactFields.length ;i++)
        {
            var fieldName = contactFields[i]
            Vue.set(vm.gridData[n],fieldName,contact[fieldName])
        }

        /*Vue.set(vm.gridData[n],"FirstName",data.FirstName)
        Vue.set(vm.gridData[n],"LastName",data.LastName)
        Vue.set(vm.gridData[n],"StageName",data.StageName)
        Vue.set(vm.gridData[n],"StageId",data.StageId)
        Vue.set(vm.gridData[n],"_ProgramInterestedIn0",data._ProgramInterestedIn0)
        Vue.set(vm.gridData[n],"OwnerName",data.OwnerName)
        Vue.set(vm.gridData[n],"OwnerId",data.OwnerId)*/

    }).fail(function (xhr,statusText,error) {
        swal({
            type:'error',
            title:'Something went wrong!',
            html:'Could not save data. <br> Error code : ' + xhr.status
        })
    })
}