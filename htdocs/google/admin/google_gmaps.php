<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 */

/**
 *	    \file       htdocs/google/admin/google_gmaps.php
 *      \ingroup    google
 *      \brief      Setup page for google module (GMaps)
 */

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && @file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && preg_match('/\/(?:custom|nltechno)([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php');
dol_include_once("/google/lib/google.lib.php");

if (!$user->admin)
    accessforbidden();

$langs->load("google@google");
$langs->load("admin");
$langs->load("other");

$def = array();
$actiontest=$_POST["test"];
$actionsave=$_POST["save"];
$action=GETPOST('action');


/*
 * Actions
 */

if ($action == 'gmap_deleteerrors')
{
    $sql="DELETE FROM ".MAIN_DB_PREFIX."google_maps WHERE result_code <> 'OK'";
    $result=$db->query($sql);
    
    if ($result)
    {
        setEventMessages($langs->trans("RecordInGeoEncodingErrorDeleted"), null);
    }
    else
    {
        setEventMessages("ErrorDeleting table goolg_maps with result_code <> 'OK'", null, 'errors');
    }
}

if ($actionsave)
{
    $db->begin();

	$res=0;
    $res+=dolibarr_set_const($db,'GOOGLE_ENABLE_GMAPS',trim($_POST["GOOGLE_ENABLE_GMAPS"]),'chaine',0,'',$conf->entity);
	$res+=dolibarr_set_const($db,'GOOGLE_ENABLE_GMAPS_CONTACTS',trim($_POST["GOOGLE_ENABLE_GMAPS_CONTACTS"]),'chaine',0,'',$conf->entity);
	$res+=dolibarr_set_const($db,'GOOGLE_ENABLE_GMAPS_MEMBERS',trim($_POST["GOOGLE_ENABLE_GMAPS_MEMBERS"]),'chaine',0,'',$conf->entity);
	$res+=dolibarr_set_const($db,'GOOGLE_GMAPS_ZOOM_LEVEL',trim($_POST["GOOGLE_GMAPS_ZOOM_LEVEL"]),'chaine',0,'',$conf->entity);
	$res+=dolibarr_set_const($db,'GOOGLE_API_SERVERKEY',trim($_POST["GOOGLE_API_SERVERKEY"]),'chaine',0,'',$conf->entity);

    if ($res == 5)
    {
        $db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}




/*
 * View
 */

$form=new Form($db);
$formadmin=new FormAdmin($db);
$formother=new FormOther($db);

$help_url='EN:Module_Google_EN|FR:Module_Google|ES:Modulo_Google';
llxHeader('',$langs->trans("GoogleSetup"),$help_url);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("GoogleSetup"),$linkback,'setup');
print '<br>';


print '<form name="googleconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';

$head=googleadmin_prepare_head();

dol_fiche_head($head, 'tabgmaps', $langs->trans("GoogleTools"));

print $langs->trans("GoogleEnableThisToolThirdParties").': ';
if ($conf->societe->enabled)
{
	print $form->selectyesno("GOOGLE_ENABLE_GMAPS",isset($_POST["GOOGLE_ENABLE_GMAPS"])?$_POST["GOOGLE_ENABLE_GMAPS"]:$conf->global->GOOGLE_ENABLE_GMAPS,1);
}
else print $langs->trans("ModuleMustBeEnabledFirst",$langs->transnoentitiesnoconv("Module1Name"));
print '<br>';

//print '<br>';
print $langs->trans("GoogleEnableThisToolContacts").': ';
if ($conf->societe->enabled)
{
	print $form->selectyesno("GOOGLE_ENABLE_GMAPS_CONTACTS",isset($_POST["GOOGLE_ENABLE_GMAPS_CONTACTS"])?$_POST["GOOGLE_ENABLE_GMAPS_CONTACTS"]:$conf->global->GOOGLE_ENABLE_GMAPS_CONTACTS,1);
}
else print $langs->trans("ModuleMustBeEnabledFirst",$langs->transnoentitiesnoconv("Module1Name"));
print '<br>';

//print '<br>';
print $langs->trans("GoogleEnableThisToolMembers").': ';
if ($conf->adherent->enabled)
{
	print $form->selectyesno("GOOGLE_ENABLE_GMAPS_MEMBERS",isset($_POST["GOOGLE_ENABLE_GMAPS_MEMBERS"])?$_POST["GOOGLE_ENABLE_GMAPS_MEMBERS"]:$conf->global->GOOGLE_ENABLE_GMAPS_MEMBERS,1);
}
else print $langs->trans("ModuleMustBeEnabledFirst",$langs->transnoentitiesnoconv("Module310Name"));
print '<br>';


print '<br>';


$var=false;
print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print '<td>'.$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "</tr>";

//print '<br>';
print '<tr '.$bc[$var].'><td>'.$langs->trans("GoogleZoomLevel").'</td><td>';
print '<input class="flat" name="GOOGLE_GMAPS_ZOOM_LEVEL" id="GOOGLE_GMAPS_ZOOM_LEVEL" value="'.(isset($_POST["GOOGLE_GMAPS_ZOOM_LEVEL"])?$_POST["GOOGLE_GMAPS_ZOOM_LEVEL"]:$conf->global->GOOGLE_GMAPS_ZOOM_LEVEL).'" size="2">';
print '</td></tr>';

print '</table>';

print '<br>';

print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print '<td>'.$langs->trans("Parameter").' ('.$langs->trans("ParametersForGoogleAPIv3Usage","Geocoding").')'."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Note")."</td>";
print "</tr>";
// Google login
print "<tr ".$bc[$var].">";
print '<td class="fieldrequired">'.$langs->trans("GOOGLE_API_SERVERKEY")."</td>";
print "<td>";
print '<input class="flat" type="text" size="64" name="GOOGLE_API_SERVERKEY" value="'.$conf->global->GOOGLE_API_SERVERKEY.'">';
print '</td>';
print '<td>';
//print $langs->trans("KeepEmptyYoUsePublicQuotaOfAPI","Geocoding API").'<br>';
print $langs->trans("AllowGoogleToLoginWithKey","https://console.developers.google.com/apis/credentials","https://console.developers.google.com/apis/credentials").'<br>';
print "</td>";
print "</tr>";

print '</table>';

print info_admin($langs->trans("EnableAPI","https://console.developers.google.com/apis/library/","https://console.developers.google.com/apis/library/","Geocoding API"));

dol_fiche_end();

print '<div align="center">';
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</div>";

print "</form>\n";


print '<a href="'.$_SERVER["PHP_SELF"].'?action=gmap_deleteerrors">'.$langs->trans("ResetGeoEncodingErrors").'</a><br>';


dol_htmloutput_mesg($mesg);

// Show message
$message='';
//$urlgooglehelp='<a href="http://www.google.com/calendar/embed/EmbedHelper_en.html" target="_blank">http://www.google.com/calendar/embed/EmbedHelper_en.html</a>';
//$message.=$langs->trans("GoogleSetupHelp",$urlgooglehelp);
//print info_admin($message);

llxFooter();

$db->close();
