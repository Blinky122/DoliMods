<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Dimitri Mouillard  <dmouillard@teclib.com>
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
 *   	\file       fiche.php
 *		\ingroup    congespayes
 *		\brief      Form and file creation of paid leave.
 *		\version    $Id: fiche.php,v 1.16 2011/09/15 11:00:00 dmouillard Exp $
 *		\author		dmouillard@teclib.com <Dimitri Mouillard>
 *		\remarks	   Form and file creation of paid leave.
 */

require('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT. "/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT. "/user/class/usergroup.class.php");
require_once(DOL_DOCUMENT_ROOT. "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT. "/core/class/CMailFile.class.php");
require_once(DOL_DOCUMENT_ROOT. "/core/class/html.formmail.class.php");

// Get parameters
$myparam = GETPOST("myparam");
$action=GETPOST('action');

// Protection if external user
if ($user->societe_id > 0) accessforbidden();

$user_id = $user->id;



/*******************************************************************
 * Actions
********************************************************************/

// Si création de la demande
if ($action == 'add')
{

    // Si pas le droit de créer une demande
    if(!$user->rights->congespayes->create_edit_read)
    {
        header('Location: fiche.php?action=request&error=CantCreate');
        exit;
    }

    $date_debut = $_POST['date_debut_year'].'-'.str_pad($_POST['date_debut_month'],2,"0",STR_PAD_LEFT).'-'.str_pad($_POST['date_debut_day'],2,"0",STR_PAD_LEFT);
    $date_fin = $_POST['date_fin_year'].'-'.str_pad($_POST['date_fin_month'],2,"0",STR_PAD_LEFT).'-'.str_pad($_POST['date_fin_day'],2,"0",STR_PAD_LEFT);
    $valideur = $_POST['valideur'];
    $description = trim($_POST['description']);
    $userID = $_POST['userID'];

    // Si pas de date de début
    if(empty($_POST['date_debut_']))
    {
        header('Location: fiche.php?action=request&error=nodatedebut');
        exit;
    }

    // Si pas de date de fin
    if(empty($_POST['date_fin_']))
    {
        header('Location: fiche.php?action=request&error=nodatefin');
        exit;
    }

    $testDateDebut = strtotime($date_debut);
    $testDateFin = strtotime($date_fin);

    // Si date de début après la date de fin
    if($testDateDebut > $testDateFin)
    {
        header('Location: fiche.php?action=request&error=datefin');
        exit;
    }

    $cp = new Congespayes($db);

    $verifCP = $cp->verifDateCongesCP($userID,$date_debut,$date_fin);

    // On vérifie si il n'y a pas déjà des congés payés sur cette période
    if(!$verifCP)
    {
        header('Location: fiche.php?action=request&error=alreadyCP');
        exit;
    }

    // Si aucun jours ouvrés dans la demande
    if($cp->getOpenDays($testDateDebut,$testDateFin) < 1)
    {
        header('Location: fiche.php?action=request&error=DureeConges');
        exit;
    }

    // Si pas de validateur choisi
    if($valideur < 1)
    {
        header('Location: fiche.php?action=request&error=Valideur');
        exit;
    }

    $cp->fk_user = $user_id;
    $cp->description = $description;
    $cp->date_debut = $date_debut;
    $cp->date_fin = $date_fin;
    $cp->fk_validator = $valideur;

    $verif = $cp->create($user_id);

    // Si pas d'erreur SQL on redirige vers la fiche de la demande
    if ($verif > 0)
    {
        header('Location: fiche.php?id='.$verif);
        exit;
    }
    else
    {
        // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
        header('Location: fiche.php?action=request&error=SQL_Create&msg='.$cp->error);
        exit;
    }

}

if($action == 'update')
{

    // Si pas le droit de modifier une demande
    if(!$user->rights->congespayes->create_edit_read)
    {
        header('Location: fiche.php?action=request&error=CantUpdate');
        exit;
    }

    $cp = new Congespayes($db);
    $cp->fetch($_POST['conges_id']);

    // Si en attente de validation
    if ($cp->statut == 1)
    {

        // Si c'est le créateur ou qu'il a le droit de tout lire / modifier
        if ($user->id == $cp->fk_user || $user->rights->congespayes->lire_tous)
        {
            $date_debut = $_POST['date_debut_year'].'-'.str_pad($_POST['date_debut_month'],2,"0",STR_PAD_LEFT).'-'.str_pad($_POST['date_debut_day'],2,"0",STR_PAD_LEFT);
            $date_fin = $_POST['date_fin_year'].'-'.str_pad($_POST['date_fin_month'],2,"0",STR_PAD_LEFT).'-'.str_pad($_POST['date_fin_day'],2,"0",STR_PAD_LEFT);
            $valideur = $_POST['valideur'];
            $description = trim($_POST['description']);

            // Si pas de date de début
            if(empty($_POST['date_debut_'])) {
                header('Location: fiche.php?id='.$_POST['conges_id'].'&action=edit&error=nodatedebut');
                exit;
            }

            // Si pas de date de fin
            if(empty($_POST['date_fin_'])) {
                header('Location: fiche.php?id='.$_POST['conges_id'].'&action=edit&error=nodatefin');
                exit;
            }

            $testDateDebut = strtotime($date_debut);
            $testDateFin = strtotime($date_fin);

            // Si date de début après la date de fin
            if($testDateDebut > $testDateFin) {
                header('Location: fiche.php?id='.$_POST['conges_id'].'&action=edit&error=datefin');
                exit;
            }

            // Si pas de valideur choisi
            if($valideur < 1) {
                header('Location: fiche.php?id='.$_POST['conges_id'].'&action=edit&error=Valideur');
                exit;
            }

            // Si pas de jours ouvrés dans la demande
            if($cp->getOpenDays($testDateDebut,$testDateFin) < 1) {
                header('Location: fiche.php?id='.$_POST['conges_id'].'&action=edit&error=DureeConges');
                exit;
            }

            $cp->description = $description;
            $cp->date_debut = $date_debut;
            $cp->date_fin = $date_fin;
            $cp->fk_validator = $valideur;

            $verif = $cp->update($user->id);

            // Si pas d'erreur SQL on redirige vers la fiche de la demande
            if($verif > 0) {
                header('Location: fiche.php?id='.$_POST['conges_id']);
                exit;
            } else {
                // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
                header('Location: fiche.php?id='.$_POST['conges_id'].'&action=edit&error=SQL_Create&msg='.$cp->error);
                exit;
            }
        }
    } else {
        header('Location: fiche.php?id='.$_POST['conges_id']);
        exit;
    }
}

// Si suppression de la demande
if ($action == 'confirm_delete'  && $_GET['confirm'] == 'yes')
{
    if($user->rights->congespayes->delete)
    {

        $cp = new Congespayes($db);
        $cp->fetch($_GET['id']);

        // Si c'est bien un brouillon
        if($cp->statut == 1) {
            // Si l'utilisateur à le droit de lire cette demande, il peut la supprimer
            if($user->id == $cp->fk_user || $user->rights->congespayes->lire_tous) {
                $cp->delete($_GET['id']);
                header('Location: index.php');
                exit;
            }
            else {
                $error = $langs->trans('ErrorCantDeleteCP');
            }
        }
    }
}

// Si envoi de la demande
if ($_GET['action'] == 'confirm_send')
{
    $cp = new Congespayes($db);
    $cp->fetch($_GET['id']);

    $userID = $user->id;

    // Si brouillon et créateur
    if($cp->statut == 1 && $userID == $cp->fk_user)
    {
        $cp->statut = 2;

        $verif = $cp->update($user->id);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if($verif > 0) {

            // A
            $destinataire = new User($db);
            $destinataire->fetch($cp->fk_validator);
            $emailTo = $destinataire->email;

            // De
            $expediteur = new User($db);
            $expediteur->fetch($cp->fk_user);
            $emailFrom = $expediteur->email;

            // Sujet
            if($conf->global->MAIN_APPLICATION_TITLE != NULL) {
                $societeName = addslashes($conf->global->MAIN_APPLICATION_TITLE);
            } else {
                $societeName = addslashes($conf->global->MAIN_INFO_SOCIETE_NOM);
            }

            $subject = stripslashes($societeName)." - Demande de congés payés à valider";

            // Contenu
            $message = "Bonjour {$destinataire->prenom},\n\n";
            $message.= "Veuillez trouver ci-dessous une demande de congés payés à valider.\n";

            $delayForRequest = $cp->getConfCP('delayForRequest');
            $delayForRequest = $delayForRequest * (60*60*24);

            $nextMonth = date('Y-m-d', time()+$delayForRequest);

            // Si l'option pour avertir le valideur en cas de délai trop court
            if($cp->getConfCP('AlertValidatorDelay')) {
                if($cp->date_debut < $nextMonth) {
                    $message.= "\n";
                    $message.= "Cette demande de congés payés à été effectué dans un";
                    $message.= " délai de moins de ".$cp->getConfCP('delayForRequest')." jours avant ceux-ci.\n";
                }
            }

            // Si l'option pour avertir le valideur en cas de solde inférieur à la demande
            if($cp->getConfCP('AlertValidatorSolde')) {
                if($cp->getOpenDays(strtotime($cp->date_debut),strtotime($cp->date_fin)) > $cp->getCPforUser($cp->fk_user)) {
                    $message.= "\n";
                    $message.= "L'utilisateur ayant fait cette demande de congés payés n'a pas le solde requis.\n";
                }
            }

            $message.= "\n";
            $message.= "- Demandeur : {$expediteur->prenom} {$expediteur->nom}\n";
            $message.= "- Période : du ".date('d/m/Y',strtotime($cp->date_debut))." au ".date('d/m/Y',strtotime($cp->date_fin))."\n";
            $message.= "- Lien : {$dolibarr_main_url_root}/congespayes/fiche.php?id={$cp->rowid}\n\n";
            $message.= "Bien cordialement,\n".$societeName;

            $mail = new CMailFile($subject,$emailTo,$emailFrom,$message);

            // Envoi du mail
            $result=$mail->sendfile();

            if(!$result) {
                header('Location: fiche.php?id='.$_GET['id'].'&error=mail&error_content='.$mail->error);
                exit;
            }

            header('Location: fiche.php?id='.$_GET['id']);
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: fiche.php?id='.$_GET['id'].'&error=SQL_Create&msg='.$cp->error);
            exit;
        }
    }
}

// Si Validation de la demande
if($_GET['action'] == 'confirm_valid')
{

    $cp = new Congespayes($db);
    $cp->fetch($_GET['id']);

    $userID = $user->id;

    // Si statut en attente de validation et valideur = utilisateur
    if($cp->statut == 2 && $userID == $cp->fk_validator)
    {

        $cp->date_valid = date('Y-m-d H:i:s', time());
        $cp->fk_user_valid = $user->id;
        $cp->statut = 3;

        $verif = $cp->update($user->id);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if($verif > 0) {

            // Retrait du nombre de jours prit
            $nbJour = $cp->getOpenDays(strtotime($cp->date_debut),strtotime($cp->date_fin));

            $soldeActuel = $cp->getCpforUser($cp->fk_user);
            $newSolde = $soldeActuel - ($nbJour*$cp->getConfCP('nbCongesDeducted'));

            // On ajoute la modification dans le LOG
            $cp->addLogCP($userID,$cp->fk_user,'Event : Prise de congés payés',$newSolde);

            // Mise à jour du solde
            $cp->updateSoldeCP($cp->fk_user,$newSolde);

            // A
            $destinataire = new User($db);
            $destinataire->fetch($cp->fk_user);
            $emailTo = $destinataire->email;

            // De
            $expediteur = new User($db);
            $expediteur->fetch($cp->fk_validator);
            $emailFrom = $expediteur->email;

            // Sujet
            if($conf->global->MAIN_APPLICATION_TITLE != NULL) {
                $societeName = addslashes($conf->global->MAIN_APPLICATION_TITLE);
            } else {
                $societeName = addslashes($conf->global->MAIN_INFO_SOCIETE_NOM);
            }

            $subject = stripslashes($societeName)." - Demande de congés payés validée";

            // Contenu
            $message = "Bonjour {$destinataire->prenom},\n\n";
            $message.= "Votre demande de congés payés du ".$cp->date_debut." au ".$cp->date_fin." vient d'être validée!\n";
            $message.= "- Valideur : {$expediteur->prenom} {$expediteur->nom}\n";
            $message.= "- Lien : {$dolibarr_main_url_root}/congespayes/fiche.php?id={$cp->rowid}\n\n";
            $message.= "Bien cordialement,\n".$societeName;


            $mail = new CMailFile($subject,$emailTo,$emailFrom,$message);

            // Envoi du mail
            $result=$mail->sendfile();

            if(!$result) {
                header('Location: fiche.php?id='.$_GET['id'].'&error=mail&error_content='.$mail->error);
                exit;
            }

            header('Location: fiche.php?id='.$_GET['id']);
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: fiche.php?id='.$_GET['id'].'&error=SQL_Create&msg='.$cp->error);
            exit;
        }

    }

}

if ($_GET['action'] == 'confirm_refuse')
{
    if($_POST['action'] == 'confirm_refuse' && !empty($_POST['detail_refuse']))
    {
        $cp = new Congespayes($db);
        $cp->fetch($_GET['id']);

        $userID = $user->id;

        // Si statut en attente de validation et valideur = utilisateur
        if($cp->statut == 2 && $userID == $cp->fk_validator) {

            $cp->date_refuse = date('Y-m-d H:i:s', time());
            $cp->fk_user_refuse = $user->id;
            $cp->statut = 5;
            $cp->detail_refuse = $_POST['detail_refuse'];

            $verif = $cp->update($user->id);

            // Si pas d'erreur SQL on redirige vers la fiche de la demande
            if($verif > 0) {

                // A
                $destinataire = new User($db);
                $destinataire->fetch($cp->fk_user);
                $emailTo = $destinataire->email;

                // De
                $expediteur = new User($db);
                $expediteur->fetch($cp->fk_validator);
                $emailFrom = $expediteur->email;

                // Sujet
                if($conf->global->MAIN_APPLICATION_TITLE != NULL) {
                    $societeName = addslashes($conf->global->MAIN_APPLICATION_TITLE);
                } else {
                    $societeName = addslashes($conf->global->MAIN_INFO_SOCIETE_NOM);
                }

                $subject = stripslashes($societeName)." - Demande de congés payés refusée";

                // Contenu
                $message = "Bonjour {$destinataire->prenom},\n\n";
                $message.= "Votre demande de congés payés ".$cp->date_debut." au ".$cp->date_fin." vient d'être refusée pour le motif suivant :\n";
                $message.= $_POST['detail_refuse']."\n\n";
                $message.= "- Valideur : {$expediteur->prenom} {$expediteur->nom}\n";
                $message.= "- Lien : {$dolibarr_main_url_root}/congespayes/fiche.php?id={$cp->rowid}\n\n";
                $message.= "Bien cordialement,\n".$societeName;


                $mail = new CMailFile($subject,$emailTo,$emailFrom,$message);

                // Envoi du mail
                $result=$mail->sendfile();

                if(!$result) {
                    header('Location: fiche.php?id='.$_GET['id'].'&error=mail&error_content='.$mail->error);
                    exit;
                }

                header('Location: fiche.php?id='.$_GET['id']);
                exit;
            } else {
                // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
                header('Location: fiche.php?id='.$_GET['id'].'&error=SQL_Create&msg='.$cp->error);
                exit;
            }

        }

    } else {
        header('Location: fiche.php?id='.$_GET['id'].'&error=NoMotifRefuse');
        exit;
    }
}

// Si Validation de la demande
if ($_GET['action'] == 'confirm_cancel' && $_GET['confirm'] == 'yes')
{
    $cp = new Congespayes($db);
    $cp->fetch($_GET['id']);

    $userID = $user->id;

    // Si statut en attente de validation et valideur = utilisateur
    if($cp->statut == 2 && $userID == $cp->fk_validator)
    {
        $cp->date_cancel = date('Y-m-d H:i:s', time());
        $cp->fk_user_cancel = $user->id;
        $cp->statut = 4;

        $verif = $cp->update($user->id);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if($verif > 0)
        {
            // A
            $destinataire = new User($db);
            $destinataire->fetch($cp->fk_user);
            $emailTo = $destinataire->email;

            // De
            $expediteur = new User($db);
            $expediteur->fetch($cp->fk_validator);
            $emailFrom = $expediteur->email;

            // Sujet
            if($conf->global->MAIN_APPLICATION_TITLE != NULL) {
                $societeName = addslashes($conf->global->MAIN_APPLICATION_TITLE);
            } else {
                $societeName = addslashes($conf->global->MAIN_INFO_SOCIETE_NOM);
            }

            $subject = stripslashes($societeName)."- Demande de congés payés annulée";

            // Contenu
            $message = "Bonjour {$destinataire->prenom},\n\n";
            $message.= "Votre demande de congés payés ".$cp->date_debut." au ".$cp->date_fin." vient d'être annulée !\n";
            $message.= "- Valideur : {$expediteur->prenom} {$expediteur->nom}\n";
            $message.= "- Lien : {$dolibarr_main_url_root}/congespayes/fiche.php?id={$cp->rowid}\n\n";
            $message.= "Bien cordialement,\n".$societeName;


            $mail = new CMailFile($subject,$emailTo,$emailFrom,$message);

            // Envoi du mail
            $result=$mail->sendfile();

            if(!$result)
            {
                header('Location: fiche.php?id='.$_GET['id'].'&error=mail&error_content='.$mail->error);
                exit;
            }

            header('Location: fiche.php?id='.$_GET['id']);
            exit;
        }
        else
        {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: fiche.php?id='.$_GET['id'].'&error=SQL_Create&msg='.$cp->error);
            exit;
        }

    }

}



/***************************************************
 * View
****************************************************/

llxHeader($langs->trans('CPTitreMenu'));

if ($_GET['action'] == 'request')
{

    // Si l'utilisateur n'a pas le droit de faire une demande
    if(!$user->rights->congespayes->create_edit_read)
    {
        print '<div class="tabBar">';
        print $langs->trans('CantCreateCP');
        print '</div>'."\n";
    }
    else
    {
        // Formulaire de demande de congés payés
        print_fiche_titre($langs->trans('MenuAddCP'));

        // Si il y a une erreur
        if(isset($_GET['error'])) {

            switch($_GET['error']) {
                case 'datefin' :
                    $msg = $langs->trans('ErrorEndDateCP');
                    break;
                case 'SQL_Create' :
                    $msg = $langs->trans('ErrorSQLCreateCP').' <b>'.htmlentities($_GET['msg']).'</b>';
                    break;
                case 'CantCreate' :
                    $msg = $langs->trans('CantCreateCP');
                    break;
                case 'Valideur' :
                    $msg = $langs->trans('InvalidValidatorCP');
                    break;
                case 'nodatedebut' :
                    $msg = $langs->trans('NoDateDebut');
                    break;
                case 'nodatedebut' :
                    $msg = $langs->trans('NoDateFin');
                    break;
                case 'DureeConges' :
                    $msg = $langs->trans('ErrorDureeCP');
                    break;
                case 'alreadyCP' :
                    $msg = $langs->trans('alreadyCPexist');
                    break;
            }

            print '<div class="tabBar">';
            print $msg;
            print '</div>'."\n";

        }

        $html = new Form($db);
        $cp = new Congespayes($db);

        $delayForRequest = $cp->getConfCP('delayForRequest');
        $delayForRequest = $delayForRequest * (60*60*24);

        $nextMonth = date('Y-m-d', time()+$delayForRequest);

        print '<script type="text/javascript">
       //<![CDATA[

       function valider(){
         if(document.demandeCP.date_debut_.value != "") {

            if(document.demandeCP.date_fin_.value != "") {

               if(document.demandeCP.valideur.value != "-1") {
                 return true;
               }
               else {
                 alert("'.$langs->transnoentities('InvalidValidatorCP').'");
                 return false;
               }

            }
            else {
              alert("'.$langs->trans('NoDateFin').'");
              return false;
            }
         }

         else {
           alert("'.$langs->trans('NoDateDebut').'");
           return false;
         }
       }

       //]]>
       </script>'."\n";

        // Formulaire de demande
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" onsubmit="return valider()" name="demandeCP">'."\n";
        print '<input type="hidden" name="action" value="add" />'."\n";
        print '<input type="hidden" name="userID" value="'.$user_id.'" />'."\n";
        print '<div class="tabBar">';
        print '<span>'.$langs->trans('DelayToRequestCP',$cp->getConfCP('delayForRequest')).'</span><br /><br />';

        $nb_conges = $cp->getCPforUser($user->id) / $cp->getConfCP('nbCongesDeducted');

        print '<span>'.$langs->trans('SoldeCPUser', round($nb_conges,0)).'</span><br /><br />';
        print '<table class="border" width="100%">';
        print '<tbody>';
        print '<tr>';
        print '<td class="fieldrequired">'.$langs->trans("DateDebCP").'</td>';
        print '<td>';
        // Si la demande ne vient pas de l'agenda
        if(!isset($_GET['datep'])) {
            $html->select_date($nextMonth,'date_debut_');
        } else {
            $date = substr($_GET['datep'],0,4)."-".substr($_GET['datep'],4,2)."-".substr($_GET['datep'],6,2);
            $html->select_date($date,'date_debut_');
        }
        print '</td>';
        print '</tr>';
        print '<tr>';
        print '<td class="fieldrequired">'.$langs->trans("DateFinCP").'</td>';
        print '<td>';
        // Si la demande ne vient pas de l'agenda
        if(!isset($_GET['datep'])) {
            $html->select_date($nextMonth,'date_fin_');
        } else {
            $date = substr($_GET['datef'],0,4)."-".substr($_GET['datef'],4,2)."-".substr($_GET['datef'],6,2);
            $html->select_date($date,'date_fin_');
        }
        print '</td>';
        print '</tr>';
        print '<tr>';
        print '<td class="fieldrequired">'.$langs->trans("ValidateByCP").'</td>';
        // Liste des utiliseurs du groupes choisi dans la config
        $idGroupValid = $cp->getConfCP('userGroup');

        $validator = new UserGroup($db,$idGroupValid);
        $valideur = $validator->listUsersForGroup();

        print '<td>';
        $html->select_users('',"valideur",1,"",0,$valideur,'');
        print '</td>';
        print '</tr>';
        print '<tr>';
        print '<td>'.$langs->trans("DescCP").'</td>';
        print '<td>';
        print '<textarea name="description" class="flat" rows="2" cols="70"></textarea>';
        print '</td>';
        print '</tr>';
        print '</tbody>';
        print '</table>';
        print '<div style="clear: both;"></div>';
        print '</div>';
        print '</from>'."\n";

        print '<center>';
        print '<input type="submit" value="'.$langs->trans("SendRequestCP").'" name="bouton" class="button">';
        print '&nbsp; &nbsp; ';
        print '<input type="button" value="'.$langs->trans("Cancel").'" class="button" onclick="history.go(-1)">';
        print '</center>';
    }

}
elseif(isset($_GET['id']))
{
    if ($error)
    {
        print '<div class="tabBar">';
        print $error;
        print '<br /><br /><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
        print '</div>';
    }
    else
    {
        // Affichage de la fiche d'une demande de congés payés
        if ($_GET['id'] > 0)
        {
            $cp = new Congespayes($db);
            $cp->fetch($_GET['id']);

            $valideur = new User($db);
            $valideur->fetch($cp->fk_validator);

            $userRequest = new User($db);
            $userRequest->fetch($cp->fk_user);

            // Utilisateur connecté
            $userID = $user->id;

            print_fiche_titre($langs->trans('TitreRequestCP'));

            // Si il y a une erreur
            if(isset($_GET['error'])) {

                switch($_GET['error']) {
                    case 'datefin' :
                        $msg = $langs->trans('ErrorEndDateCP');
                        break;
                    case 'SQL_Create' :
                        $msg = $langs->trans('ErrorSQLCreateCP').' <b>'.htmlentities($_GET['msg']).'</b>';
                        break;
                    case 'CantCreate' :
                        $msg = $langs->trans('CantCreateCP');
                        break;
                    case 'Valideur' :
                        $msg = $langs->trans('InvalidValidatorCP');
                        break;
                    case 'nodatedebut' :
                        $msg = $langs->trans('NoDateDebut');
                        break;
                    case 'nodatedebut' :
                        $msg = $langs->trans('NoDateFin');
                        break;
                    case 'DureeConges' :
                        $msg = $langs->trans('ErrorDureeCP');
                        break;
                    case 'NoMotifRefuse' :
                        $msg = $langs->trans('NoMotifRefuseCP');
                        break;
                    case 'mail' :
                        $msg = $langs->trans('ErrorMailNotSend').'<br /><b>'.$_GET['error_content'].'</b>';
                        break;
                }

                print '<div class="tabBar">';
                print $msg;
                print '</div>';

            }

            // On vérifie si l'utilisateur à le droit de lire cette demande
            if($user->id == $cp->fk_user || $user->rights->congespayes->lire_tous) {

                if($_GET['action'] == 'delete' && $cp->statut == 1) {
                    if($user->rights->congespayes->delete) {
                        $html = new Form($db);

                        $ret=$html->form_confirm("fiche.php?id=".$_GET['id'],$langs->trans("TitleDeleteCP"),$langs->trans("ConfirmDeleteCP"),"confirm_delete", '', 0, 1);
                        if ($ret == 'html') print '<br />';
                    }
                }

                // Si envoi en validation
                if($_GET['action'] == 'sendToValidate' && $cp->statut == 1 && $userID == $cp->fk_user) {
                    $html = new Form($db);

                    $ret=$html->form_confirm("fiche.php?id=".$_GET['id'],$langs->trans("TitleToValidCP"),$langs->trans("ConfirmToValidCP"),"confirm_send", '', 0, 1);
                    if ($ret == 'html') print '<br />';
                }

                // Si validation de la demande
                if($_GET['action'] == 'valid' && $cp->statut == 2 && $userID == $cp->fk_validator) {
                    $html = new Form($db);

                    $ret=$html->form_confirm("fiche.php?id=".$_GET['id'],$langs->trans("TitleValidCP"),$langs->trans("ConfirmValidCP"),"confirm_valid", '', 0, 1);
                    if ($ret == 'html') print '<br />';
                }

                // Si refus de la demande
                if($_GET['action'] == 'refuse' && $cp->statut == 2 && $userID == $cp->fk_validator) {
                    $html = new Form($db);

                    $array_input = array(array('type'=>"text",'label'=>"Entrez ci-dessous un motif de refus :",'name'=>"detail_refuse",'size'=>"50",'value'=>""));
                    $ret=$html->form_confirm("fiche.php?id=".$_GET['id']."&action=confirm_refuse",$langs->trans("TitleRefuseCP"),"","confirm_refuse",$array_input,"",0);
                    if ($ret == 'html') print '<br />';
                }

                // Si annulation de la demande
                if($_GET['action'] == 'cancel' && $cp->statut == 2 && $userID == $cp->fk_validator) {
                    $html = new Form($db);

                    $ret=$html->form_confirm("fiche.php?id=".$_GET['id'],$langs->trans("TitleCancelCP"),$langs->trans("ConfirmCancelCP"),"confirm_cancel", '', 0, 1);
                    if ($ret == 'html') print '<br />';
                }


                print '<div class="tabBar">';


                if($_GET['action'] == 'edit' && $user->id == $cp->fk_user && $cp->statut == 1)
                {
                    $edit = true;
                    print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$_GET['id'].'">'."\n";
                    print '<input type="hidden" name="action" value="update"/>'."\n";
                    print '<input type="hidden" name="conges_id" value="'.$_GET['id'].'" />'."\n";

                    $html = new Form($db);
                }

                print '<table class="border" style="float: left; width:40%;">';
                print '<tbody>';
                print '<tr class="liste_titre">';
                print '<td colspan="2">'.$langs->trans("InfosCP").'</td>';
                print '</tr>';

                print '<tr>';
                print '<td width="50%">ID</td>';
                print '<td>'.$cp->rowid.'</td>';
                print '</tr>';

                if(!$edit) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateDebCP').'</td>';
                    print '<td>'.date('d-m-Y', strtotime($cp->date_debut)).'</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateDebCP').'</td>';
                    print '<td>';
                    $html->select_date($cp->date_debut,'date_debut_');
                    print '</td>';
                    print '</tr>';
                }

                if(!$edit) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateFinCP').'</td>';
                    print '<td>'.date('d-m-Y', strtotime($cp->date_fin)).'</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateFinCP').'</td>';
                    print '<td>';
                    $html->select_date($cp->date_fin,'date_fin_');
                    print '</td>';
                    print '</tr>';
                }
                print '<tr>';
                print '<td>'.$langs->trans('NbUseDaysCP').'</td>';
                print '<td>'.$cp->getOpenDays(strtotime($cp->date_debut),strtotime($cp->date_fin)).'</td>';
                print '</tr>';
                if(!$edit) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DescCP').'</td>';
                    print '<td>'.nl2br($cp->description).'</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td>'.$langs->trans('DescCP').'</td>';
                    print '<td><textarea name="description" class="flat" rows="2" cols="70">'.$cp->description.'</textarea></td>';
                    print '</tr>';
                }
                print '</tbody>';
                print '</table>'."\n";

                print '<div style="width: 4%; float: left;">&nbsp;</div>';

                print '<div style="float: left;width: 40%;">'."\n";

                print '<table class="border" style="width: 100%;">'."\n";
                print '<tbody>';
                print '<tr class="liste_titre">';
                print '<td colspan="2">'.$langs->trans("InfosWorkflowCP").'</td>';
                print '</tr>';

                if(!$edit) {
                    print '<tr>';
                    print '<td width="50%">'.$langs->trans('ValidateByCP').'</td>';
                    print '<td>'.$valideur->getNomUrl(1).'</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td width="50%">'.$langs->trans('ValidateByCP').'</td>';
                    // Liste des utiliseurs du groupes choisi dans la config
                    $idGroupValid = $cp->getConfCP('userGroup');

                    $validator = new UserGroup($db,$idGroupValid);
                    $valideur = $validator->listUsersForGroup();

                    print '<td>';
                    $html->select_users($cp->fk_validator,"valideur",1,"",0,$valideur,'');
                    print '</td>';
                    print '</tr>';
                }

                print '<tr>';
                print '<td>'.$langs->trans('RequestByCP').'</td>';
                print '<td>'.$userRequest->getNomUrl(1).'</td>';
                print '</tr>';
                print '<tr>';
                print '<td>'.$langs->trans('DateCreateCP').'</td>';
                print '<td>'.$cp->date_create.'</td>';
                print '</tr>';
                if($cp->statut == 3) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateValidCP').'</td>';
                    print '<td>'.$cp->date_valid.'</td>';
                    print '</tr>';
                }
                if($cp->statut == 4) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateCancelCP').'</td>';
                    print '<td>'.$cp->date_cancel.'</td>';
                    print '</tr>';
                }
                if($cp->statut == 5) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateRefusCP').'</td>';
                    print '<td>'.$cp->date_refuse.'</td>';
                    print '</tr>';
                }
                print '<tr>';
                print '<td>'.$langs->trans('StatutCP').'</td>';
                print '<td><b>'.$cp->getStatutCP($cp->statut).'</b></td>';
                print '</tr>';
                if($cp->statut == 5) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DetailRefusCP').'</td>';
                    print '<td>'.$cp->detail_refuse.'</td>';
                    print '</tr>';
                }
                print '</tbody>';
                print '</table>';

                print '<div style="clear: both;"></div>'."\n";

                print '</div>';
                print '<div style="clear: both;"></div>'."\n";
                print '</div>';

                if ($edit)
                {
                    print '<center>';
                    if($user->rights->congespayes->create_edit_read && $_GET['action'] == 'edit' && $cp->statut == 1)
                    {
                        print '<input type="submit" value="'.$langs->trans("UpdateButtonCP").'" class="button">';
                    }
                    print '</center>';

                    print '</form>';
                }

                if (! $edit)
                {
                    print '<br />';
                    print '<div style="float: right;">'."\n";

                    // Boutons d'actions

                    if($user->rights->congespayes->create_edit_read && $_GET['action'] != 'edit' && $cp->statut == 1) {
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=edit" class="butAction" style="float: left;">'.$langs->trans("EditCP").'</a>';
                    }
                    if($user->rights->congespayes->delete && $cp->statut == 1) {
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=delete" class="butAction" style="float: left;">'.$langs->trans("DeleteCP").'</a>';
                    }
                    if($user->id == $cp->fk_user && $cp->statut == 1) {
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=sendToValidate" class="butAction" style="float: left;">'.$langs->trans("SendToValidationCP").'</a>';
                    }

                    // Si le statut est en attente de validation et que le valideur est connecté
                    if($userID == $cp->fk_validator && $cp->statut == 2) {
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=valid" class="butAction" style="float: left;">'.$langs->trans("ActionValidCP").'</a>';
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=refuse" class="butAction" style="float: left;">'.$langs->trans("ActionRefuseCP").'</a>';
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=cancel" class="butAction" style="float: left;">'.$langs->trans("ActionCancelCP").'</a>';
                    }

                    print '</div>';
                }

            } else {
                print '<div class="tabBar">';
                print $langs->trans('ErrorUserViewCP');
                print '<br /><br /><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
                print '</div>';
            }

        } else {
            print '<div class="tabBar">';
            print $langs->trans('ErrorIDFicheCP');
            print '<br /><br /><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
            print '</div>';
        }

    }

}

// End of page
$db->close();
llxFooter();
?>
