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

    return $filterContacts;
}

?>


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
            <th v-for="(field, index) of fields" @click="sortBy(field)"  :class="headerClass(index,field)">
                {{ headerNames[index] }}
                <span class="arrow" :class="order > 0 ? 'asc' : 'dsc'"></span>
            </th>
            <th class="editHeader">Edit</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="record of filteredResults">
            <td v-for="(field, index) of fields" :class="{idCell:field=='Id'}" :data-ownerid="field=='OwnerName' ? record[field] : ''">
                {{record[field]}}
            </td>
            <td class="editBtn" @click="editRecord(record)">Edit</td>
        </tr>
        </tbody>
    </table>
    <!--<pre>{{$data | json }}</pre>-->
</div>




<script>
    //var contacts = <?php /*echo json_encode(fetchContacts());*/ ?>

    // bootstrap the demo
    var vm = new Vue({
        el: '#app',
        data: {
            order: 1,
            sortField: 'Id',
            searchQuery: '',
            fields: ['Id', 'FirstName','LastName','StageName','_ProgramInterestedIn0','OwnerName'],
            gridData: [],
            headerNames: ['Id', 'First Name','Last Name','Stage','Program','Owner']
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
            }
        },
        mounted: function () {
            $.ajax({
                type: 'POST',
                url: './contact.json',
                data: "query=contacts",
                success: function (response) {
                    vm.gridData = response
                }
            })
        },
        methods: {
            sortBy: function (field) {
                this.sortField = field
                this.order = this.order * -1
            },
            headerClass: function (index,field) {
                var cssClass="";
                if(this.sortField==field){cssClass+="active";}
                if(index>=0&&index<2){cssClass+=" one";}
                if(index>=2&&index<4){cssClass+=" two";}
                if(index>=4&&index<6){cssClass+=" three";}
                return cssClass
            },
            editRecord: function (record){
                var fname = record.FirstName!=undefined?record.FirstName:"";
                var lname = record.LastName!=undefined?record.LastName:"";
                var stage = record.StageName!=undefined?record.StageName:"";
                var prog = record._ProgramInterestedIn0!=undefined?record._ProgramInterestedIn0:"";
                var owner = record.OwnerName!=undefined?record.OwnerName:"";
                //sweet alert modal, which handles the edit form and ajax request to save data
                swal({
                    title: 'Edit Applicant',
                    onOpen:function () {
                        var form = new Vue({
                            el:'#editForm',
                            data:{
                                Testfield:[1,2,3,4,5]
                            }
                        })
                    },
                    html:
                    '<form id="editForm" method="post" action="gridview.php">' +
                    '<div class="field"><label for="fname">First Name</label><input type="text" id="fname" value="'+ fname + '"></div>' +
                    '<div class="field"><label for="lname">Last Name</label><input type="text" id="lname" value="'+ lname + '"></div>' +
                    '<div class="field"><label for="stage">Stage</label><input type="text" id="stage" value="'+ stage + '"></div>' +
                    '<div class="field"><label for="prog">Program</label><input type="text" id="prog" value="'+ prog + '"></div>' +
                    '<div class="field"><label for="owner">Owner</label><span v-for="n of Testfield">{{ n }}</span></div>' +
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
                            data.StageName = $('#stage').val();
                            data._ProgramInterestedIn0 = $('#prog').val();
                            data.OwnerName = $('#owner').val();

                            $.ajax({
                                type: 'POST',
                                url: 'addUpdate.php',
                                data: data
                            }).done(function (response,statusText,xhr) {
                                swal({
                                    type: 'success',
                                    title: 'Saved Data',
                                    html: response
                                })

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
                                Vue.set(vm.gridData[n],"_ProgramInterestedIn0",data._ProgramInterestedIn0)
                                //vm.gridData[n].FirstName = data.FirstName;

                                /*Vue.nextTick(function () {});*/
                                //vm.gridData.splice(n,1);
                                //vm.gridData.push(data);

                            }).fail(function (xhr,statusText,error) {
                                swal({
                                    type:'error',
                                    title:'Something went wrong!',
                                    html:'Could not save data. <br> Error code : ' + xhr.status
                                })
                            })
                        })
                    }
                })
            }
        }
    })


</script>
