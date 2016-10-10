<link rel="stylesheet" href="style.css" type="text/css" media="screen" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/sweetalert2/5.1.1/sweetalert2.min.css" type="text/css"/>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/vue/2.0.1/vue.js"></script>
<script src="https://cdn.jsdelivr.net/sweetalert2/5.1.1/sweetalert2.min.js"></script>
<script src="scripts.js"></script>


<div id="app">
    <div><input class="searchBox" placeholder="Search" v-model="searchQuery"></div>
    <table>
        <thead>
        <tr>
            <th v-for="(field, index) of displayFields" @click="sortBy(field)"  :class="headerClass(index,field)">
                {{ headerNames[index] }}
                <span class="arrow" :class="order > 0 ? 'asc' : 'dsc'"></span>
            </th>
            <th class="editHeader">Edit</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="record of filteredResults">
            <td v-for="(field, index) of displayFields" :class="{idCell:field=='Id'}" :data-ownerid="field=='OwnerName' ? record[field] : ''">
                {{ record[field] }}
            </td>
            <td class="editBtn" @click="editRecord(record)">Edit</td>
        </tr>
        </tbody>
    </table>
    <!--<pre>{{$data | json }}</pre>-->
</div>




<script>
    var applicantStages;
    var owners;

    // bootstrap the Vue app
    var vm = new Vue({
        el: '#app',
        data: {
            order: 1,
            sortField: 'LastName',
            searchQuery: '',
            fields: ['FirstName','LastName','StageName','_ProgramInterestedIn0','OwnerName','_PaidAppFee','_PersonalReference',
                '_PasterReferenceReceived','_HighSchoolTranscriptReceived','_MostRecentCollegeTranscriptsReceived','_CollegeTranscript2Received',
                '_College3TranscriptsReceived','_PaidRoomDeposit0','_EnrolledInClasses0','_FilledoutPTQuestionnaire0','_FilledOutRoommateQuestionnaire',
                '_SentArrivalInformation0','_FilledOutImmunizationForm0','_AppliedforFAFSA','_CompletedVFAOStudentInterview','_AppliedforStudentLoansoptional'
                ,'_SentEmergencyContactInformation','_JoinedFacebook','StageId','OwnerId','Id'],

            gridData: [],
            headerNames: ['First Name','Last Name','Stage','Program','Owner','Paid App Fee','Personal Ref','Pastor Ref','HS Transcript','Clg Transcript 1','Clg Transcript 2',
                'Clg Transcript 3','Room Deposit','Enrolled In Classes','PT Questionnaire','Roommate Questionnaire','Arrival Form','Immunization Form','Applied to FAFSA',
                'VFAO Interview','Applied to Student Loans','Emergency Contact Form','Joined Facebook Group']
        },
        computed:{
            filteredResults: function () {
                var searchTerm = this.searchQuery && this.searchQuery.toLowerCase()
                var sortfield = this.sortField
                var order = this.order || 1
                var data = this.gridData

                if(searchTerm)
                {
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
            displayFields: function() {
                //remove as many fields from the "fields" variable that you dont want displayed
                return this.fields.slice(0,this.fields.length-3)
            }
        },
        mounted: function () {
            $.ajax({
                type: 'POST',
                url: 'api.php', //./contact.json
                data: "query=getContacts"
            }).done(function (response) {
                var contactdata = JSON.parse(response)// JSON.parse(response)
                vm.gridData = contactdata
                //console.log(vm.gridData)
            }).fail(function (xhr, statusText, error) {
                console.log(error)
            })

            $.ajax({
                type: 'POST',
                url: 'api.php', //./contact.json
                data: "query=getStages"
            }).done(function (response) {
                applicantStages = JSON.parse(response)
            })

            $.ajax({
                type: 'POST',
                url: 'api.php', //./contact.json
                data: "query=getUsers"
            }).done(function (response) {
                owners = JSON.parse(response)
            })
        },
        methods: {
            showField:function (field) {
                if(field!="StageId" && field!="OwnerId" && field!="Id") return field
            },
            sortBy: function (field) {
                this.sortField = field
                this.order = this.order * -1
            },
            headerClass: function (index,field) {
                var cssClass="";
                if(this.sortField==field){cssClass+=" active";}
                if(index>=0&&index<6){cssClass+=" one";}
                if(index>=6&&index<12){cssClass+=" two";}
                if(index>=12&&index<22){cssClass+=" three";}
                return cssClass
            },
            editRecord: function (record){
                var fname = record.FirstName!=undefined?record.FirstName:"";
                var lname = record.LastName!=undefined?record.LastName:"";
                var stage = record.StageName!=undefined?record.StageName:"";
                var stageId = record.StageId!=undefined?record.StageId:"";
                var prog = record._ProgramInterestedIn0!=undefined?record._ProgramInterestedIn0:"";
                var owner = record.OwnerName!=undefined?record.OwnerName:"";
                var ownerId = record.OwnerId!=undefined?record.OwnerId:"";
                //sweet alert modal, which handles the edit form and ajax request to save data
                swal({
                    title: 'Edit Applicant',
                    onOpen:function () {
                        var formVm = new Vue({
                            el:'#editForm',
                            data:{
                                stages: applicantStages,
                                stageId: stageId,
                                owners: owners,
                                ownerId: ownerId
                            },
                            methods:{
                                stageSelected:function (id) {
                                    if(this.stageId == id || id==0)return "selected"
                                },
                                ownerSelected:function (id) {
                                    if(this.ownerId == id || id==0)return "selected"
                                }
                            }
                        });
                    },
                    html:
                    '<form id="editForm" method="post" action="gridview.php">' +
                        '<div class="field"><label for="fname">First Name</label><input type="text" id="fname" value="'+ fname + '"></div>' +
                        '<div class="field"><label for="lname">Last Name</label><input type="text" id="lname" value="'+ lname + '"></div>' +
                        '<div class="field"><label for="stage">Stage</label><select id="stage">'+
                            '<option :selected="stageSelected(0)" value="unselected">Select One...</option>' +
                            '<option :selected="stageSelected(n.Id)" v-for="n of stages" :value="n.Id">{{ n.StageName }}</option>' +
                    '   </select></div>' +
                        '<div class="field"><label for="prog">Program</label><input type="text" id="prog" value="'+ prog + '"></div>' +
                        '<div class="field"><label for="owner">Owner</label><select id="owner">' +
                            '<option :selected="ownerSelected(0)" value="unselected">Select One...</option>'+
                            '<option :selected="ownerSelected(n.Id)" v-for="n of owners" :value="n.Id">{{n.FirstName}} {{n.LastName}} </option>' +
                        '</select></div>' +
                    '</form>',
                    showCloseButton: true,
                    showCancelButton: true,
                    confirmButtonText:'Save',
                    cancelButtonText:'Cancel',
                    allowOutsideClick:false,
                    allowEscapeKey:false,
                    showLoaderOnConfirm: true,
                    preConfirm: function () {
                        return new Promise(function(resolve,reject) {
                            var data = {};
                            data.Id = record.Id;
                            data.FirstName = $('#fname').val();
                            data.LastName= $('#lname').val();
                            data._ProgramInterestedIn0 = $('#prog').val();
                            data.query = "saveContact";

                            var stageVal = $("#stage").val();

                            if(stageVal!="unselected") {
                                data.StageId = stageVal;
                                data.StageName = $("#stage option:selected").text();
                            }

                            var ownerVal = $("#owner").val();
                            if(ownerVal!="unselected") {
                                data.OwnerId = ownerVal;
                                data.OwnerName = $("#owner option:selected").text()
                            }

                            $.ajax({
                                type: 'POST',
                                url: 'api.php',
                                data: data
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

                                Vue.set(vm.gridData[n],"FirstName",data.FirstName)
                                Vue.set(vm.gridData[n],"LastName",data.LastName)
                                Vue.set(vm.gridData[n],"StageName",data.StageName)
                                Vue.set(vm.gridData[n],"StageId",data.StageId)
                                Vue.set(vm.gridData[n],"_ProgramInterestedIn0",data._ProgramInterestedIn0)
                                Vue.set(vm.gridData[n],"OwnerName",data.OwnerName)
                                Vue.set(vm.gridData[n],"OwnerId",data.OwnerId)

                            }).fail(function (xhr,statusText,error) {
                                swal({
                                    type:'error',
                                    title:'Something went wrong!',
                                    html:'Could not save data. <br> Error code : ' + xhr.status
                                })
                            })
                        })
                    }
                }).done()
            }
        }
    })


</script>
