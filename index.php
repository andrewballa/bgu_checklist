<!--replace these dev libraries with prod versions-->
<link rel="stylesheet" href="scripts/style.css" type="text/css" media="screen"/>
<link rel="stylesheet" href="scripts/loader.css" type="text/css" media="screen"/>
<link rel="stylesheet" href="scripts/sweetalert/sweetalert2.min.css" type="text/css"/>

<script src="scripts/jquery/jquery-3.1.1.js"></script>
<script src="scripts/vue/vue2.min.js"></script>
<script src="scripts/sweetalert/sweetalert2.min.js"></script>
<script src="scripts/global.js"></script>

<div id="app" style="display:none">
    <!--<pre>{{$data | json }}</pre>-->
    <div class="filterFields">
        <input id="searchBox" placeholder="Search" v-model="searchQuery">
        <select class="ddlFilter" v-model="progFilter">
            <option class="pink" value="">Program</option>
            <option v-for="n of progCategories" :value="n.category">{{ n.category }}</option>
        </select>

        <select class="ddlFilter" v-model="ownerFilter">
            <option class="pink" value="">Owner</option>
            <option v-for="n of owners" :value="n.FirstName + ' ' + n.LastName">{{n.FirstName}} {{n.LastName }}</option>
        </select>
    </div>

    <div style="">
    <div id="header">
        <div class="hcell" v-for="(field, index) of displayFields" @click="sortBy(field)" :class="cellClass(index,field,'th')">
            {{ headerNames[index] }}
            <span class="arrow" :class="order > 0 ? 'asc' : 'dsc'"></span>
        </div>
    </div>
    </div>

    <table>
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

    var programs = ['MA Intercultural Ministry Leadership','MA Intercultural Ministry Studies','MA Intercultural Ministry Education',
        'BA Intercultural Ministry Studies and Bible and Theology', 'AA Intercultural Ministry','Certificate in Bible and Missions','Certificate in Pre-Field Preparation',
        'LEAD Worship','LEAD Media'];

    var progCategories =[
        {
            "category":"Undergraduate",
            "programs":["BA Intercultural Ministry Studies and Bible and Theology","AA Intercultural Ministry","Certificate in Bible and Missions","Certificate in Pre-Field Preparation"]
        },
        {
            "category":"Graduate",
            "programs":["MA Intercultural Ministry Leadership","MA Intercultural Ministry Studies","MA Intercultural Ministry Education"]
        },
        {
            "category":"LEAD",
            "programs":["LEAD Worship","LEAD Media"]
        }
    ];
    var applicantStages;
    var userData;


    function loadApp() {
        swal({
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
        }).then(function () {
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
            }
        )

    }

    function bootstrapApp() {

        // bootstrap the Vue app
        var vm = new Vue({
            el: '#app',
            data: {
                order: 1,
                sortField: 'LastName',
                searchQuery: '',
                progFilter:'',
                ownerFilter:'',
                fields: ['FirstName', 'LastName', '_ProgramInterestedIn0', 'StageName', 'OwnerName', '_PaidAppFee', '_PersonalReference', '_PasterReferenceReceived',
                    '_TeacherEmployerReferenceReceived', '_HighSchoolTranscriptReceived', '_MostRecentCollegeTranscriptsReceived', '_CollegeTranscript2Received',
                    '_College3TranscriptsReceived', '_PaidRoomDeposit0', '_EnrolledInClasses0', '_FilledoutPTQuestionnaire0', '_FilledOutRoommateQuestionnaire',
                    '_SentArrivalInformation0', '_FilledOutImmunizationForm0', '_AppliedforFAFSA', '_CompletedVFAOStudentInterview', '_AppliedforStudentLoansoptional',
                    '_SentEmergencyContactInformation', '_JoinedFacebook', '_AdditionalItemsNeeded', '_AdditionalItems', 'LeadID', 'StageID', 'OwnerID', 'Id'],

                gridData: [],
                headerNames: ['First Name', 'Last Name', 'Program', 'Stage', 'Owner', 'Paid App Fee', 'Pers Ref', 'Pstr Ref', 'Teach Ref', 'HS Tran',
                    'Col Tran 1', 'Col Tran 2', 'Col Tran 3', 'Room Depo', 'Enroll Class', 'PT Quest.', 'Rmate Quest.', 'Arrive Form',
                    'Immun Form', 'FAFSA Apply', 'VFAO Intrvw', 'Apply Stnt Loans', 'Emrgcy Cont Info', 'Join FB Group', 'Adtnl Items Need?','Adtnl Items'],
                owners:[],
                programs:programs,
                progCategories:progCategories
            },
            computed: {
                filteredResults: function () {
                    var searchTerm = this.searchQuery && this.searchQuery.toLowerCase()
                    var progFilter = this.progFilter && this.progFilter.toLowerCase()
                    var ownerFilter = this.ownerFilter && this.ownerFilter.toLowerCase()
                    var sortfield = this.sortField
                    var order = this.order || 1
                    var data = this.gridData

                    if (searchTerm) {
                        data = filterTerm(searchTerm)
                    }

                    if (ownerFilter) {
                        data = filterTerm(ownerFilter)
                    }

                    if (progFilter) {
                        var finalResult = [];

                        var j;
                        if(progFilter=="undergraduate") j=0;
                        if(progFilter=="graduate") j=1;
                        if(progFilter=="lead") j=2;

                        var progArray = progCategories[j].programs
                        var filterData = [];
                        for(var i=0;i<progArray.length;i++) {
                            filterData = filterTerm(progArray[i].toLowerCase())
                            finalResult = finalResult.concat(filterData)
                        }
                        data = finalResult
                    }

                    if (sortfield) {
                        data = data.slice().sort(function (a, b) {
                            a = a[sortfield]
                            b = b[sortfield]
                            return (a === b ? 0 : a > b ? 1 : -1) * order
                        })
                    }

                    function filterTerm(term) {
                        var filteredData = data.filter(function (record) {
                            return Object.keys(record).some(function (field) {
                                return String(record[field]).toLowerCase().indexOf(term) > -1
                            })
                        })
                        return filteredData
                    }

                    return data
                },
                displayFields: function () {
                    //remove as many fields from the "fields" variable that you dont want displayed
                    return this.fields.slice(0, this.fields.length - 4)
                }
            },
            mounted: function () {
                //get all Contacts from Infusionsoft
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

                //get all Application stages from Infusionsoft
                $.ajax({
                    type: 'POST',
                    url: 'api.php', //./contact.json
                    data: "query=getStages"
                }).done(function (response) {
                    applicantStages = JSON.parse(response)
                });

                //Get all users from Infusionsoft
                $.ajax({
                    type: 'POST',
                    url: 'api.php', //./contact.json
                    data: "query=getUsers"
                }).done(function (response) {
                    userData = JSON.parse(response)
                    vm.owners = userData
                });

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
                    var n = getRecordIndex(vm.gridData, record.Id)
                    //sweet alert modal, which handles the edit form and ajax request to save data
                    swal({
                        title: 'Edit Applicant',
                        onOpen: function () {
                            var formVm = new Vue({
                                el: '#editForm',
                                data: {
                                    stages: applicantStages,
                                    owners: userData,
                                    formData: vm.gridData,
                                    r: n,
                                    ddlWaived: ['No', 'Yes', 'Waived'],
                                    ddlBinary: ['No', 'Yes'],
                                    ddlNotNeeded: ['No', 'Yes', 'Not Needed'],
                                    //these program strings need to be exactly copied from infusionsoft.
                                    programs:programs,
                                    progCategories:progCategories

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
                        html: '<form id="editForm" method="post" action="gridview.php">' +
                        '<div class="field"><label>First Name</label><input type="text" id="FirstName_input" :value="formData[r].FirstName"></div>' +
                        '<div class="field"><label>Last Name</label><input type="text" id="LastName_input" :value="formData[r].LastName"></div>' +

                        '<div class="field"><label>Program</label><select id="_ProgramInterestedIn0_input">' +
                        '<option :selected="ddlSelected(0)" value="unselected">Select One...</option>' +
                        '<option :selected="ddlSelected(n,\'_ProgramInterestedIn0\')" v-for="(n,i) of programs" :value="n">{{ n }}</option>' +
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

        loadApp();

    })
</script>
