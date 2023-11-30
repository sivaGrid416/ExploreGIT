{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}


<span>
    <h4>{'LBL_IMPORT_MAP_FIELDS'|@vtranslate:$MODULE}</h4>
</span>
<p style='color:red;float:right'>Note: Map the fields in Header Section to the CRM field to create entry in VB Panel</p>
<hr>
<div id="savedMapsContainer">{include file="Import_Saved_Maps.tpl"|@vtemplate_path:'Import'}</div>
<input type="hidden" id="filename">
<div>{include file="Import_Mapping.tpl"|@vtemplate_path:'Import'}</div>
<div class="form-inline" style="padding-bottom: 10%;">
    <input type="checkbox" name="save_map" id="save_map">&nbsp;&nbsp;<label for="save_map">{'LBL_SAVE_AS_CUSTOM_MAPPING'|@vtranslate:$MODULE}</label>
    &nbsp;&nbsp;<input type="text" name="save_map_as" id="save_map_as" class = "form-control">
</div>


<script>
    jQuery(document).ready(function() {
// $('input[type="file"]').change(function(e){
                    // var fileName = e.target.files[0].name;
                    // alert(fileName);
                    // localStorage.setItem('Filename', fileName);
                    
                    const filename = localStorage.getItem("filename");
                    // alert(filename);
                    document.getElementById("filename").value = filename;
                    localStorage.removeItem("filename");
                   
                    // $('#uploaded_file').html(fileName);
                // });
});
</script>

{if !$IMPORTABLE_FIELDS}
	{assign var=IMPORTABLE_FIELDS value=$AVAILABLE_FIELDS}
{/if}
{include file="Import_Default_Values_Widget.tpl"|@vtemplate_path:'Import' IMPORTABLE_FIELDS=$IMPORTABLE_FIELDS}

