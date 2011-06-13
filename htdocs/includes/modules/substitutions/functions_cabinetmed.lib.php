<?php
/* Copyright (C) 2011 Laurent Destailleur         <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/includes/modules/substitutions/functions_cabinetmed.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains functions for plugin cabinetmed.
 *	\version		$Id: functions_cabinetmed.lib.php,v 1.4 2011/06/13 17:34:58 eldy Exp $
 */


/**
 * 		Function called to complete substitution array
 * 		functions xxx_completesubstitutionarray are called by make_substitutions()
 *		@param		substitutionarray	Array with substitution key=>val
 *		@param		langs				Output langs
 *		@param		object				Object to use to get values
 * 		@return		None. The entry parameter $substitutionarray is modified
 */
function cabinetmed_completesubstitutionarray(&$substitutionarray,$langs,$object)
{
	global $conf,$db;
	if (is_object($object))
	{
        dol_include_once('/cabinetmed/class/cabinetmedcons.class.php');
        dol_include_once('/cabinetmed/class/cabinetmedexambio.class.php');
        dol_include_once('/cabinetmed/class/cabinetmedexamother.class.php');

        $isbio=0;
        $isother=0;

        $outcome=new CabinetmedCons($db);
	    $result1=$outcome->fetch(GETPOST('idconsult'));

	    if (GETPOST('idbio') > 0)
	    {
	        $exambio=new CabinetmedExamBio($db);
            $result2=$exambio->fetch(GETPOST('idbio'));
            $isbio=1;
	    }

        if (GETPOST('idradio') > 0)
        {
	        $examother=new CabinetmedExamOther($db);
            $result3=$examother->fetch(GETPOST('idradio'));
            $isother=1;
        }

        if ($isother || $isbio) $substitutionarray['examshows']='Les bilans suivants mettent en évidence,';
        else $substitutionarray['examshows']='';

        if ($isother)
        {
            $substitutionarray['examother_title']='Bilan imagerie:';
            $substitutionarray['examother_conclusion']=$examother->concprinc;
        }
        else
        {
            $substitutionarray['examother_title']='';
            $substitutionarray['examother_conclusion']='';
        }
        if ($isbio)
        {
            $substitutionarray['exambio_title']='Bilan Biologique:';
            $substitutionarray['exambio_conclusion']=$exambio->conclusion;
        }
        else
        {
            $substitutionarray['exambio_title']='';
            $substitutionarray['exambio_conclusion']='';
        }

        $substitutionarray['outcome_comment']=GETPOST('outcome_comment');
        $substitutionarray['outcome_diagnostic']=$outcome->diaglesprinc;
        $substitutionarray['outcome_treatment']=$outcome->traitementprescrit;
	}
}

