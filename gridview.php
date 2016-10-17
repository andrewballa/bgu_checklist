<!--replace these dev libraries with prod versions-->
<link rel="stylesheet" href="scripts/style.css" type="text/css" media="screen"/>
<link rel="stylesheet" href="scripts/loader.css" type="text/css" media="screen"/>
<link rel="stylesheet" href="scripts/sweetalert/sweetalert2.css" type="text/css"/>

<script src="scripts/jquery/jquery-3.1.1.js"></script>
<script src="scripts/vue/vue2.js"></script>
<script src="scripts/sweetalert/sweetalert2.js"></script>
<script src="scripts/global.js"></script>

<div id="test"></div>
<!--<div id="loader" class="loader loader--snake"></div>-->
<div id="app" style="display:none">
    <!--<pre>{{$data | json }}</pre>-->
    <div><input class="searchBox" placeholder="Search" v-model="searchQuery"></div>
    <table>
        <thead>
        <tr>
            <th v-for="(field, index) of displayFields" @click="sortBy(field)" :class="cellClass(index,field,'th')">
                {{ headerNames[index] }}
                <span class="arrow" :class="order > 0 ? 'asc' : 'dsc'"></span>
            </th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="record of filteredResults">
            <td title="Click To Edit" v-for="(field, index) of displayFields" @click="editRecord(record)" :class="cellClass(index,field,'td',record[field])" >
                {{ fieldValue(record[field]) }}
            </td>
        </tr>
        </tbody>
    </table>
</div>


<script>
    function loadApp() {
/*        swal({
            type: 'question',
            title: 'Enter Password',
            allowEscapeKey: false,
            allowOutsideClick: false,
            showLoaderOnConfirm: true,
            html: '<input id="pass" type="password"/>',
            preConfirm: function () {
                return new Promise(function (resolve, reject) {
                    $.ajax({
                        type: 'POST',
                        url: 'api.php', //./contact.json
                        data: "query=checkPass"
                    }).done(function (response) {
                        var listener = response
                        if ($('#pass').val() == response) {
                            resolve();
                        }
                        else {
                            swal({
                                type: 'error',
                                title: 'Wrong Password',
                                allowEscapeKey: false,
                                allowOutsideClick: false,
                            }).then(function () {
                                    loadApp()
                                }
                            )
                        }
                    })
                })
            }
        }).then(function () {*/
                swal({
                    title: 'Loading...',
                    html:   '<p>Loading data from Infusionsoft, takes about 10-15 seconds</p>' +
                            '<div id="loader" class="loader loader--snake"></div>',
                    allowEscapeKey: false,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    onOpen: function () {
                        bootstrapApp()
                    }

                }).done();
            //}
        //)

    }


    function bootstrapApp() {
        var applicantStages;
        var owners;

        // bootstrap the Vue app
        var vm = new Vue({
            el: '#app',
            data: {
                order: 1,
                sortField: 'LastName',
                searchQuery: '',
                fields: ['FirstName', 'LastName', '_ProgramInterestedIn0', 'StageName', 'OwnerName', '_PaidAppFee', '_PersonalReference', '_PasterReferenceReceived',
                    '_TeacherEmployerReferenceReceived', '_HighSchoolTranscriptReceived', '_MostRecentCollegeTranscriptsReceived', '_CollegeTranscript2Received',
                    '_College3TranscriptsReceived', '_PaidRoomDeposit0', '_EnrolledInClasses0', '_FilledoutPTQuestionnaire0', '_FilledOutRoommateQuestionnaire',
                    '_SentArrivalInformation0', '_FilledOutImmunizationForm0', '_AppliedforFAFSA', '_CompletedVFAOStudentInterview', '_AppliedforStudentLoansoptional',
                    '_SentEmergencyContactInformation', '_JoinedFacebook', '_AdditionalItemsNeeded', '_AdditionalItems', 'LeadID', 'StageID', 'OwnerID', 'Id'],

                gridData: [],
                headerNames: ['First Name', 'Last Name', 'Program', 'Stage', 'Owner', 'Paid App Fee', 'Pers Ref', 'Pstr Ref', 'Teach Ref', 'HS Tran',
                    'Col Tran 1', 'Col Tran 2', 'Col Tran 3', 'Room Depo', 'Enroll Class', 'PT Quest.', 'Rmate Quest.', 'Arrive Form',
                    'Immun Form', 'FAFSA Apply', 'VFAO Intrvw', 'Apply Stnt Loans', 'Emrgcy Cont Info', 'Join FB Group', 'Adtnl Items Need?','Adtnl Items']
            },
            computed: {
                filteredResults: function () {
                    var searchTerm = this.searchQuery && this.searchQuery.toLowerCase()
                    var sortfield = this.sortField
                    var order = this.order || 1
                    var data = this.gridData

                    if (searchTerm) {
                        data = data.filter(function (row) {
                            return Object.keys(row).some(function (key) {
                                return String(row[key]).toLowerCase().indexOf(searchTerm) > -1
                            })
                        })
                    }
                    if (sortfield) {
                        data = data.slice().sort(function (a, b) {
                            a = a[sortfield]
                            b = b[sortfield]
                            return (a === b ? 0 : a > b ? 1 : -1) * order
                        })
                    }
                    return data
                },
                displayFields: function () {
                    //remove as many fields from the "fields" variable that you dont want displayed
                    return this.fields.slice(0, this.fields.length - 4)
                }
            },
            mounted: function () {
                //get the contact data from the API
                $.ajax({
                    type: 'POST',
                    url: './test_data/contact.json', // ./test_data/contact.json
                    data: "query=getContacts"
                }).done(function (response) {
                    var contactdata = response// JSON.parse(response)
                    $('#app').show();
                    vm.gridData = contactdata
                    swal.close()
                }).fail(function (xhr, statusText, error) {
                    console.log(error)
                })

                $.ajax({
                    type: 'POST',
                    url: 'api.php', //./contact.json
                    data: "query=getStages"
                }).done(function (response) {
                    applicantStages = JSON.parse(response)
                    //$("#test").html("<pre>" + response + "</pre>")
                })

                //Get all users from the API
                $.ajax({
                    type: 'POST',
                    url: 'api.php', //./contact.json
                    data: "query=getUsers"
                }).done(function (response) {
                    owners = JSON.parse(response)
                })
            },
            methods: {
                sortBy: function (field) {
                    this.sortField = field
                    this.order = this.order * -1
                },
                cellClass: function (index, field, type, fieldVal) {
                    var cssClass = "";
                    if(type=="th") {
                        if (this.sortField == field) {
                            cssClass += " active";
                        }
                    }
                    if(type=="td" && fieldVal!=undefined)
                    {
                        fieldVal = fieldVal.toLowerCase()
                        if(fieldVal=="yes" || fieldVal=="not needed") cssClass += " green"
                        if(fieldVal=="no" ) cssClass += " red"

                    }
                    if (index >= 0 && index < 5) {
                        cssClass += " one";
                    }
                    if (index >= 5 && index < 13) {
                        cssClass += " two";
                    }
                    if (index >= 13 && index < 26) {
                        cssClass += " three";
                    }
                    return cssClass
                },
                fieldValue :function (fieldVal) {
                    if(fieldVal!=undefined) {
                        fieldVal = fieldVal.length > 50 ? fieldVal.substr(0, 50)+"..." : fieldVal;
                        return fieldVal
                    }
                },
                cellStatus: function (fieldVal) {

                },
                editRecord: function (record) {
                    console.log(record)
                    console.log(owners)
                    var n = getRecordIndex(vm.gridData, record.Id)
                    //sweet alert modal, which handles the edit form and ajax request to save data
                    swal({
                        title: 'Edit Applicant',
                        onOpen: function () {
                            var formVm = new Vue({
                                el: '#editForm',
                                data: {
                                    stages: applicantStages,
                                    owners: owners,
                                    formData: vm.gridData,
                                    r: n,
                                    ddlWaived: ['No', 'Yes', 'Waived'],
                                    ddlBinary: ['No', 'Yes'],
                                    ddlNotNeeded: ['No', 'Yes', 'Not Needed'],
                                    //these program strings need to be exactly copied from infusionsoft.
                                    programs:['MA Intercultural Leadership','MA Intercultural Studies','MA Intercultural Education','BA Intercultural Studies and Theology',
                                        'AA Intercultural Studies','Certificate in Bible and Missions','Certificate in Pre-Field Preparation','LEAD Worship','LEAD Media']

                                },
                                methods: {
                                    ddlSelected: function (val, field) {
                                        if (record[field] == val || val == 0) return "selected"
                                    }
                                },
                                computed: {
                                    ProgramData:function () {
                                        var program= this.formData[n]._ProgramInterestedIn0;
                                        if(program!=undefined) {
                                            program = program.length > 50 ? program.substr(0, 50) + "..." : program;
                                            return program
                                        }
                                    }
                                }
                            });
                        },
                        /*['FirstName', 'LastName', '_ProgramInterestedIn0', 'StageName', 'OwnerName', '_PaidAppFee', '_PersonalReference','_PasterReferenceReceived',
                         '_TeacherEmployerReferenceReceived', '_HighSchoolTranscriptReceived', '_MostRecentCollegeTranscriptsReceived', '_CollegeTranscript2Received',
                         '_College3TranscriptsReceived', '_PaidRoomDeposit0', '_EnrolledInClasses0', '_FilledoutPTQuestionnaire0','_FilledOutRoommateQuestionnaire',
                         '_SentArrivalInformation0', '_FilledOutImmunizationForm0', '_AppliedforFAFSA','_CompletedVFAOStudentInterview','_AppliedforStudentLoansoptional',
                         '_SentEmergencyContactInformation', '_JoinedFacebook','_AdditionalItemsNeeded','_AdditionalItems', 'LeadID', 'StageID', 'OwnerID', 'Id']*/
                        html: '<form id="editForm" method="post" action="gridview.php">' +
                        '<div class="field"><label>First Name</label><input type="text" id="FirstName_input" :value="formData[r].FirstName"></div>' +
                        '<div class="field"><label>Last Name</label><input type="text" id="LastName_input" :value="formData[r].LastName"></div>' +

                        '<div class="field"><label>Program</label><select id="_ProgramInterestedIn0_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_ProgramInterestedIn0\')" v-for="n of programs" :value="n">{{ n }}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Stage</label><select id="StageName_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n.Id,\'StageID\')" v-for="n of stages" :value="n.Id">{{ n.StageName }}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Owner</label><select id="OwnerName_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n.Id,\'OwnerID\')" v-for="n of owners" :value="n.Id">{{n.FirstName}} {{n.LastName}} </option>' +
                        '</select></div>' +

                        '<div id="appSteps">' +
                        '<div class="field"><label>Paid App Fee</label><select id="_PaidAppFee_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_PaidAppFee\')" v-for="n of ddlWaived" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Personal Ref?</label><select id="_PersonalReference_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_PersonalReference\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Pastoral Ref?</label><select id="_PasterReferenceReceived_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_PasterReferenceReceived\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Teacher/Employer Ref?</label><select id="_TeacherEmployerReferenceReceived_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_TeacherEmployerReferenceReceived\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>HS/GED Transcript?</label><select id="_HighSchoolTranscriptReceived_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_HighSchoolTranscriptReceived\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Clg Transcript 1</label><select id="_MostRecentCollegeTranscriptsReceived_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_MostRecentCollegeTranscriptsReceived\')" v-for="n of ddlNotNeeded" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Clg Transcript 2</label><select id="_CollegeTranscript2Received_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_CollegeTranscript2Received\')" v-for="n of ddlNotNeeded" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Clg Transcript 3</label><select id="_College3TranscriptsReceived_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_College3TranscriptsReceived\')" v-for="n of ddlNotNeeded" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Room Deposit?</label><select id="_PaidRoomDeposit0_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_PaidRoomDeposit0\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Enrolled?</label><select id="_EnrolledInClasses0_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_EnrolledInClasses0\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>PT Form?</label><select id="_FilledoutPTQuestionnaire0_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_FilledoutPTQuestionnaire0\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Roomate Form?</label><select id="_FilledOutRoommateQuestionnaire_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_FilledOutRoommateQuestionnaire\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Arrival Info?</label><select id="_SentArrivalInformation0_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_SentArrivalInformation0\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Immunization?</label><select id="_FilledOutImmunizationForm0_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_FilledOutImmunizationForm0\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>FAFSA?</label><select id="_AppliedforFAFSA_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_AppliedforFAFSA\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>VFAO Interview?</label><select id="_CompletedVFAOStudentInterview_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_CompletedVFAOStudentInterview\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Applied for loans?</label><select id="_AppliedforStudentLoansoptional_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_AppliedforStudentLoansoptional\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Emergency Info?</label><select id="_SentEmergencyContactInformation_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_SentEmergencyContactInformation\')" v-for="n of ddlBinary" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field"><label>Joined Facebook?</label><select id="_JoinedFacebook_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_JoinedFacebook\')" v-for="n of ddlNotNeeded" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '</div>' +

                        '<div class="field down"><label>Additional Items Needed?</label><select id="_AdditionalItemsNeeded_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_AdditionalItemsNeeded\')" v-for="n of ddlWaived" :value="n">{{n}}</option>' +
                        '</select></div>' +
                        '<div class="field down"><label>Additional Items</label><textarea rows="5" cols="50" id="_AdditionalItems_input" :value="formData[r]._AdditionalItems"/></div>' +

                        '</form>',
                        showCloseButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Save',
                        cancelButtonText: 'Cancel',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showLoaderOnConfirm: true,
                        preConfirm: function () {
                            return new Promise(function () {
                                contactSave(record, vm)

                            })
                        }
                    }).done()
                }
            }
        })
    }


    $(document).ready(function () {
        loadApp()
    })
</script>
