<!--replace these dev libraries with prod versions-->
<link rel="stylesheet" href="scripts/style.css" type="text/css" media="screen" />
<link rel="stylesheet" href="scripts/sweetalert/sweetalert2.css" type="text/css"/>

<script src="scripts/jquery/jquery-3.1.1.js"></script>
<script src="scripts/vue/vue2.js"></script>
<script src="scripts/sweetalert/sweetalert2.js"></script>
<script src="scripts/global.js"></script>

<div id="test"></div>

<div id="app" style="display:none">
    <!--<pre>{{$data | json }}</pre>-->
    <div><input class="searchBox" placeholder="Search" v-model="searchQuery"></div>
    <table>
        <thead>
        <tr>
            <th v-for="(field, index) of displayFields" @click="sortBy(field)"  :class="headerClass(index,field)">
                {{ headerNames[index] }}
                <span class="arrow" :class="order > 0 ? 'asc' : 'dsc'"></span>
            </th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(record,x) of filteredResults">
            <td title="Click To Edit" v-for="(field, index) of displayFields" :class="{idCell:field=='Id'}" @click="editRecord(record)">
                {{ record[field] }}
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
                }).then(function () {*/
                    var applicantStages;
                    var owners;
                    $('#app').show();

                    // bootstrap the Vue app
                    var vm = new Vue({
                        el: '#app',
                        data: {
                            order: 1,
                            sortField: 'LastName',
                            searchQuery: '',
                            fields: ['FirstName', 'LastName', 'StageName', '_ProgramInterestedIn0', 'OwnerName', '_PaidAppFee', '_PersonalReference',
                                '_PasterReferenceReceived', '_HighSchoolTranscriptReceived', '_MostRecentCollegeTranscriptsReceived', '_CollegeTranscript2Received',
                                '_College3TranscriptsReceived', '_PaidRoomDeposit0', '_EnrolledInClasses0', '_FilledoutPTQuestionnaire0', '_FilledOutRoommateQuestionnaire',
                                '_SentArrivalInformation0', '_FilledOutImmunizationForm0', '_AppliedforFAFSA', '_CompletedVFAOStudentInterview', '_AppliedforStudentLoansoptional'
                                , '_SentEmergencyContactInformation', '_JoinedFacebook', 'StageId', 'OwnerId', 'Id'],

                            gridData: [],
                            headerNames: ['First Name', 'Last Name', 'Stage', 'Program', 'Owner', 'Paid App Fee', 'Personal Ref', 'Pastor Ref', 'HS Transcript', 'Clg Transcript 1', 'Clg Transcript 2',
                                'Clg Transcript 3', 'Room Deposit', 'Enrolled In Classes', 'PT Questionnaire', 'Roommate Questionnaire', 'Arrival Form', 'Immunization Form', 'Applied to FAFSA',
                                'VFAO Interview', 'Applied to Student Loans', 'Emergency Contact Form', 'Joined Facebook Group']
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
                                return this.fields.slice(0, this.fields.length - 3)
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
                                vm.gridData = contactdata
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
                            showField: function (field) {
                                if (field != "StageId" && field != "OwnerId" && field != "Id") return field
                            },
                            sortBy: function (field) {
                                this.sortField = field
                                this.order = this.order * -1
                            },
                            headerClass: function (index, field) {
                                var cssClass = "";
                                if (this.sortField == field) {
                                    cssClass += " active";
                                }
                                if (index >= 0 && index < 6) {
                                    cssClass += " one";
                                }
                                if (index >= 6 && index < 12) {
                                    cssClass += " two";
                                }
                                if (index >= 12 && index < 23) {
                                    cssClass += " three";
                                }
                                return cssClass
                            },
                            editRecord: function (record) {

                                var n = getRecordIndex(vm.gridData,record.Id)
                                var ownerId = record.OwnerId != undefined ? record.OwnerId : "";
                                //sweet alert modal, which handles the edit form and ajax request to save data
                                swal({
                                    title: 'Edit Applicant',
                                    onOpen: function () {
                                        var formVm = new Vue({
                                            el: '#editForm',
                                            data: {
                                                stages: applicantStages,
                                                stageId: record.StageID,
                                                owners: owners,
                                                ownerId: record.OwnerId,
                                                gridData: vm.gridData,
                                                r:n
                                            },
                                            methods: {
                                                stageSelected: function (id) {
                                                    if (this.stageId == id || id == 0)return "selected"
                                                },
                                                ownerSelected: function (id) {
                                                    if (this.ownerId == id || id == 0)return "selected"
                                                }
                                            }
                                        });
                                    },
                                    html:
                                    '<form id="editForm" method="post" action="gridview.php">' +
                                        '<div class="field"><label>First Name</label><input type="text" id="fname" :value="gridData[r].FirstName"></div>' +
                                        '<div class="field"><label>Last Name</label><input type="text" id="lname" :value="gridData[r].LastName"></div>' +
                                        '<div class="field"><label>Stage</label><select id="stage">' +
                                            '<option :selected="stageSelected(0)" value="unselected">Select One...</option>' +
                                            '<option :selected="stageSelected(n.Id)" v-for="n of stages" :value="n.Id">{{ n.StageName }}</option>' +
                                        '</select></div>' +
                                        '<div class="field"><label>Program</label><input type="text" id="prog" :value="gridData[r]._ProgramInterestedIn0"></div>' +
                                        '<div class="field"><label>Owner</label><select id="owner">' +
                                            '<option :selected="ownerSelected(0)" value="unselected">Select One...</option>' +
                                            '<option :selected="ownerSelected(n.Id)" v-for="n of owners" :value="n.Id">{{n.FirstName}} {{n.LastName}} </option>' +
                                        '</select></div>'+
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
                                            contactSave(record,vm)
                                        })
                                    }
                                }).done()
                            }
                        }
                    })
/*                })//
            }//

        })//*/
    }
    $(document).ready(function () {
        loadApp()
    })
</script>
