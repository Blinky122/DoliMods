<?php
/* Copyright (C) 2010-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/filemanage/admin/filemanager.php
 *	\ingroup    filemanager
 *	\brief      Setup page for filemanager module
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && file_exists("../../../../../main.inc.php")) $res=@include("../../../../../main.inc.php");
if (! $res && preg_match('/\/nltechno([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");
dol_include_once("/filemanager/class/filemanagerroots.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

// Security check
if (!$user->admin)
accessforbidden();

$langs->load("admin");
$langs->load("filemanager@filemanager");
$langs->load("errors");

/*
 * Actions
 */
if ($_GET["action"] == 'delete')
{
	$error=0;

	$filemanagerroots=new FilemanagerRoots($db);
	$result=$filemanagerroots->fetch($_GET["id"]);
	if ($result > 0)
	{
		$result=$filemanagerroots->delete($user);
		if ($result <= 0)
		{
			$mesg=$filemanagerroots->error;
		}
		else
		{
			$_POST["action"]='';
		}
	}
}

if ($_POST["action"] == 'setparam')
{
    $param='FILEMANAGER_DISABLE_COLORSYNTAXING';
    $value=$_POST['FILEMANAGER_DISABLE_COLORSYNTAXING'];
    dolibarr_set_const($db,$param,$value,'chaine',0,'',$conf->entity);
}


if ($_POST["action"] == 'set')
{
	$error=0;
	if (empty($_POST["FILEMANAGER_ROOT_LABEL"]))
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("LabelForRootFileManager")).'</div>';
		$error++;
	}
	if (empty($_POST["FILEMANAGER_ROOT_PATH"]))
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PathForRootFileManager")).'</div>';
		$error++;
	}
	if (! empty($_POST["FILEMANAGER_ROOT_PATH"]) && ! is_dir($_POST["FILEMANAGER_ROOT_PATH"]))
	{
		$mesg='<div class="error">'.$langs->trans("ErrorDirNotFound",$_POST["FILEMANAGER_ROOT_PATH"]).'</div>';
		$error++;
	}

	if (! $error)
	{
		$filemanagerroots=new FilemanagerRoots($db);
		$filemanagerroots->rootlabel=$_POST["FILEMANAGER_ROOT_LABEL"];
		$filemanagerroots->rootpath=$_POST["FILEMANAGER_ROOT_PATH"];
		$result=$filemanagerroots->create($user);
		if ($result <= 0)
		{
			$mesg=$filemanagerroots->error;
		}
		else
		{
			$_POST["action"]='';
		}
	}
}



/*
 * View
 */

$form=new Form($db);

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("FileManagerSetup"),$linkback,'setup');
print '<br>';

//if ($mesg) print '<div class="error">'.$langs->trans($mesg).'</div><br>';
if ($mesg) print $mesg.'<br>';


$h=0;
$head[$h][0] = $_SERVER["PHP_SELF"];
$head[$h][1] = $langs->trans("Setup");
$head[$h][2] = 'tabsetup';
$h++;

$head[$h][0] = 'about.php';
$head[$h][1] = $langs->trans("About");
$head[$h][2] = 'tababout';
$h++;

dol_fiche_head($head, 'tabsetup', '');


// Param
$var=true;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setparam">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td><td align="center">'.$langs->trans("Value").'</td>';
print '<td>&nbsp;</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>';
print $langs->trans("UseColorSyntaxing");
print '</td><td align="center">';
print $form->selectyesno("FILEMANAGER_DISABLE_COLORSYNTAXING",$conf->global->FILEMANAGER_DISABLE_COLORSYNTAXING);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';


print '<br><br>';


print_titre($langs->trans("AddRootPath"));

// Mode
$var=true;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("Add").'</td><td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LabelForRootFileManager").'</td>';
print '<td>';
print '<input size="12" type="text" name="FILEMANAGER_ROOT_LABEL" value="'.$_POST["FILEMANAGER_ROOT_LABEL"].'">';
print '</td><td>MyRoot</td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("PathForRootFileManager").'</td>';
print '<td>';
print '<input size="50" type="text" name="FILEMANAGER_ROOT_PATH" value="'.$_POST["FILEMANAGER_ROOT_PATH"].'">';
print '</td><td>/home/mydir, c:/</td></tr>';

print '</table>';

print '<center><input type="submit" class="button" value="'.$langs->trans("Add").'"></center>';

print "</form>\n";

dol_fiche_end();


print '<br>';


print_titre($langs->trans("ListForRootPath"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("LabelForRootFileManager").'</td><td>'.$langs->trans("PathForRootFileManager").'</td>';
print '<td align="right">&nbsp;</td>';
print "</tr>\n";

$sql = "SELECT";
$sql.= " t.rowid,";
$sql.= " t.datec,";
$sql.= " t.rootlabel,";
$sql.= " t.rootpath,";
$sql.= " t.note,";
$sql.= " t.position,";
$sql.= " t.entity";
$sql.= " FROM ".MAIN_DB_PREFIX."filemanager_roots as t";
$sql.= " WHERE entity = ".$conf->entity;

dol_syslog($sql);
$resql=$db->query($sql);
if ($resql)
{
	$var=false;
	while ($obj=$db->fetch_object($resql))
	{
		$var=!$var;
		print '<tr '.$bc[$var].'><td>'.$obj->rootlabel.'</td><td>'.$obj->rootpath.'</td><td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$obj->rowid.'">'.img_delete().'</a></td></tr>';
	}
}
else
{
	dol_print_error($db);
}
print '</table>';

print '<br>';

print info_admin($langs->trans("NoteOnFileManagerPathLocation")).'<br>';


llxFooter();
?>
