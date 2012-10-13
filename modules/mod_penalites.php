<?php

/*
 * EpiKnet Idle RPG (EIRPG)
 * Copyright (C) 2005-2012 Francis D (Homer) & EpiKnet
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. if not, see <http://www.gnu.org/licenses/>.
 */

//TODO : Ignorer les netsplits

/**
 * Module mod_penalites
 * Gestion des pénalités
 * Module indispensable au fonctionnement du jeu.
 *
 * @author Homer
 * @created 10 septembre 2005
 * @modified 10 septembre 2005
 */
class penalites
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
    var $name;    //Nom du module
    var $version; //Version du module
    var $desc;    //Description du module
    var $depend;  //Modules dont nous sommes dépendants

    //Variables supplémentaires
    var $expPenalite;
    var $penPrivmsg, $penNotice, $penNick, $penQuit, $penPart, $penKick;
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////

    function loadModule()
    {
        //Constructeur; initialisateur du module
        //S'éxécute lors du (re)chargement du bot ou d'un REHASH
        global $irc, $irpg, $db;

        /* Renseignement des variables importantes */
        $this->name    = "mod_penalites";
        $this->version = "0.9.0";
        $this->desc    = "Module de gestion des pénalités";
        $this->depend  = array("core/0.5.0");

        //Recherche de dépendances
        if (!$irpg->checkDepd($this->depend)) {
            die("$this->name: dépendance non résolue\n");
        }

        //Validation du fichier de configuration spécifique au module
        $cfgKeys    = array("expPenalite", "penPrivmsg", "penNotice", "penNick", "penQuit", "penPart", "penKick");
        $cfgKeysOpt = array("");

        if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
            die($this->name . ": Vérifiez votre fichier de configuration.\n");
        }

        //Initialisation des paramètres du fichier de configuration
        $this->expPenalite = $irpg->readConfig($this->name, "expPenalite");
        $this->penPrivmsg  = $irpg->readConfig($this->name, "penPrivmsg");
        $this->penNotice   = $irpg->readConfig($this->name, "penNotice");
        $this->penNick     = $irpg->readConfig($this->name, "penNick");
        $this->penQuit     = $irpg->readConfig($this->name, "penQuit");
        $this->penPart     = $irpg->readConfig($this->name, "penPart");
        $this->penKick     = $irpg->readConfig($this->name, "penKick");
    }

///////////////////////////////////////////////////////////////

    function unloadModule()
    {
        //Destructeur; décharge le module
        //S'éxécute lors du SHUTDOWN du bot ou d'un REHASH
        global $irc, $irpg, $db;
    }

///////////////////////////////////////////////////////////////

    function onConnect()
    {
        global $irc, $irpg, $db;
    }

///////////////////////////////////////////////////////////////

    function onPrivmsgCanal($nick, $user, $host, $message)
    {
        global $irc, $irpg, $db;
        $this->penalite($nick, "PRIVMSG", $this->penPrivmsg, strlen($message));
    }

///////////////////////////////////////////////////////////////


    function onPrivmsgPrive($nick, $user, $host, $message)
    {
        global $irc, $irpg, $db;
    }

///////////////////////////////////////////////////////////////

    function onNoticeCanal($nick, $user, $host, $message)
    {
        global $irc, $irpg, $db;

        $this->penalite($nick, "NOTICE", $this->penNotice, strlen($message));
    }

///////////////////////////////////////////////////////////////

    function onNoticePrive($nick, $user, $host, $message)
    {
        global $irc, $irpg, $db;

        $this->penalite($nick, "NOTICE", $this->penNotice, strlen($message));
    }

///////////////////////////////////////////////////////////////

    function onJoin($nick, $user, $host, $channel)
    {
        global $irc, $irpg, $db;
    }

///////////////////////////////////////////////////////////////

    function onPart($nick, $user, $host, $channel)
    {
        global $irc, $irpg, $db;

        $this->penalite($nick, "PART", $this->penPart);

        //Suppression de l'utilisateur de la table IRC
        $table = $db->prefix."IRC";
        if ($nick != $irc->me) {
            $channel = strtoupper($channel);
            $db->req("DELETE FROM $table WHERE Nick = '$nick' And Channel = '$channel'");

            //On enlève l'utilisateur du tableau des utilisateurs en ligne
            $username = $irpg->getUsernameByNick($nick);
            unset($irpg->mod["core"]->users["$username"]);
        }
    }

///////////////////////////////////////////////////////////////

    function onNick($nick, $user, $host, $newnick)
    {
        global $irc, $irpg, $db;

        //Modification du pseudo de l'utilisateur dans la table IRC et dans le tableau
        $username = $irpg->getUsernameByNick($nick);
        $table = $db->prefix . "IRC";

        $db->req("UPDATE $table SET Nick='$newnick' WHERE Nick='$nick'");
        $irpg->mod["core"]->users["$username"] = $newnick;

        //Fix entré vide..
        unset($irpg->mod["core"]->users[""]);

        $this->penalite($newnick, "NICK", $this->penNick);
    }

///////////////////////////////////////////////////////////////

    function onKick($nick, $user, $host, $channel, $nickkicked)
    {
        global $irc, $irpg, $db;

        $this->penalite($nickkicked, "KICK", $this->penKick);

        //Suppression de l'utilisateur de la table IRC
        $table = $db->prefix . "IRC";
        if ($nick != $irc->me) {
            $channel = strtoupper($channel);
            $db->req("DELETE FROM $table WHERE Nick = '$nickkicked' And Channel = '$channel'");

            //On enlève l'utilisateur du tableau des utilisateurs en ligne
            $username = $irpg->getUsernameByNick($nickkicked);
            unset($irpg->mod["core"]->users["$username"]);
        }
    }

///////////////////////////////////////////////////////////////

    function onCTCP($nick, $user, $host, $ctcp)
    {
        global $irc, $irpg, $db;
    }

///////////////////////////////////////////////////////////////

    function onQuit($nick, $user, $host, $reason)
    {
        global $irc, $irpg, $db;

        $this->penalite($nick, "QUIT", $this->penQuit);

        //Suppression de l'utilisateur de la table IRC
        $table = $db->prefix . "IRC";
        if ($nick != $irc->me) {
            $db->req("DELETE FROM $table WHERE Nick = '$nick'");
            //On enlève l'utilisateur du tableau des utilisateurs en ligne
            $username = $irpg->getUsernameByNick($nick);
            unset($irpg->mod["core"]->users["$username"]);
        }
    }

///////////////////////////////////////////////////////////////

    function on5Secondes()
    {
        global $irc, $irpg;
    }

///////////////////////////////////////////////////////////////

    function on10Secondes()
    {
        global $irc, $irpg;
    }

///////////////////////////////////////////////////////////////


    function on15Secondes()
    {
        global $irc, $irpg, $db;
    }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

    function penalite($nick, $quoi, $valeur, $multiplicateur = 1)
    {
        global $irc, $irpg, $db;

        $tbPerso = $db->prefix . "Personnages";
        $tbIRC   = $db->prefix . "IRC";

        $i = 0;
        $req = $db->getRows("SELECT Id_Personnages, Level FROM $tbPerso WHERE Id_Personnages
                             IN (SELECT Pers_Id FROM $tbIRC WHERE Nick='$nick' And NOT ISNULL(Pers_Id))");

        while ($i != count($req)) {
            $level    = $req[$i]["Level"];
            $expo     = $this->expPenalite;
            $penalite = round((($valeur * $multiplicateur) * pow($expo, $level)), 0);

            if ($penalite > 0) {
                $cpenalite = $irpg->convSecondes($penalite);
                $pid       = $req[$i]["Id_Personnages"];
                $perso     = $irpg->getNomPersoByPID($pid);
                $db->req("UPDATE $tbPerso SET Next=Next+$penalite WHERE Id_Personnages='$pid'");
                $next  = $db->getRows("SELECT Next FROM $tbPerso WHERE Id_Personnages='$pid'");
                $next  = $next[0]["Next"];
                $cnext = $irpg->convSecondes($next);
                $irpg->Log($pid, "PENAL_$quoi", "$penalite", "$next");

                if ($quoi != "QUIT") { //Le contraire serait débile, non? :)
                    $irc->notice($nick, "Pénalité d'une durée de $cpenalite ajouté à votre personnage $perso "
                        . "pour $quoi. Prochain niveau dans $cnext.");
                }
            }
            $i++;
        }
    }

///////////////////////////////////////////////////////////////
}
?>
