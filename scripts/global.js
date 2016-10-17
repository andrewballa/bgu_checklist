function getRecordIndex(array, recordId) {
    var n = 0;
    var result = $.grep(array, function (element, index) {
        if (element.Id == recordId) {
            n = index;
        }
    });
    return n;
}

function contactSave(record, vm) {

    var contact = {};

    //get all the fields of the datagrid and create a contact record
    //with each of the corresponding form elements
    for (var j = 0; j < vm.fields.length; j++) {
        var field = vm.fields[j];
        var formElement = '#' + field + '_input';
        var fieldVal = $(formElement).val();

        if ($(formElement).is('input') || $(formElement).is('textarea')) {
            contact[field] = fieldVal;
        }
        if ($(formElement).is('select')) {
            if (fieldVal != "unselected") {
                contact[field] = $(formElement + " option:selected").text();
                if (field == "StageName") {
                    contact.StageID = fieldVal;
                }
                if (field == "OwnerName") {
                    contact.OwnerID = fieldVal;
                }
            }
        }
    }


    contact.query = "saveContact";
    contact.Id = record.Id;
    contact.LeadID = record.LeadID;

    console.log(contact)
    $.ajax({
        type: 'POST',
        url: 'api.php',
        data: contact //contact
    }).done(function (response) {
        console.log(response);
        swal({
            type: 'success',
            title: 'Saved Data',
            html: response
        }).done()

        var n = getRecordIndex(vm.gridData, record.Id)

        var contactFields = Object.keys(contact);
        for (var i = 0; i < contactFields.length; i++) {
            var fieldName = contactFields[i]
            //Vue.set(vm.gridData[n],fieldName,contact[fieldName])
            vm.gridData[n][fieldName] = contact[fieldName];
        }

    }).fail(function (xhr, statusText, error) {
        console.log(error)
        swal({
            type: 'error',
            title: 'Something went wrong!',
            html: 'Could not save data. <br> Error code : ' + xhr.status
        })
    })
}