<?PHP
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       	scripts/cron/batch_fournisseur_updateturnover.php
 *	\ingroup    	fournisseur
 *	\brief      	Update table Calcul le CA genere par chaque fournisseur et met a jour les tables fournisseur_ca et produit_ca
 *	\deprecated		Ce script et ces tables ne sont pas utilisees car graph generes dynamiquement maintenant.
 *	\version		$Id: batch_fournisseur_updateturnover.php,v 1.2 2010/08/18 11:29:14 eldy Exp $
 */

// Test si mode CLI
$sapi_type = php_sapi_name();
$script_file=__FILE__;
if (preg_match('/([^\\\/]+)$/',$script_file,$reg)) $script_file=$reg[1];

if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer $script_file en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
	exit;
}

/*
 if (! isset($argv[1]) || ! $argv[1]) {
 print "Usage: $script_file now\n";
 exit;
 }
 */

// Recupere env dolibarr
$version='$Revision: 1.2 $';
$path=preg_replace('/'.$script_file.'/','',$_SERVER["PHP_SELF"]);

require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/cron/functions_cron.lib.php");

print '***** '.$script_file.' ('.$version.') *****'."\n";
print '--- start'."\n";

$error=0;
$verbose = 0;

$now = gmmktime();
$year = strftime('%Y',$now);

for ($i = 1 ; $i < sizeof($argv) ; $i++)
{
	if ($argv[$i] == "-v")
	{
		$verbose = 1;
	}
	if ($argv[$i] == "-vv")
	{
		$verbose = 2;
	}
	if ($argv[$i] == "-vvv")
	{
		$verbose = 3;
	}
	if ($argv[$i] == "-y")
	{
		$year = $argv[$i+1];
	}
}


$db->begin();

$result=batch_fournisseur_updateturnover($year);

if ($result > 0)
{
	$db->commit();
	print '--- end ok'."\n";
}
else
{
	print '--- end error code='.$result."\n";
	$db->rollback();
}

?>
