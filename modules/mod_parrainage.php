<?php

/*
 * EpiKnet Idle RPG (EIRPG)
 * Copyright (C) 2005-2012 Francis D (Homer), cedricpc & EpiKnet
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

/*
 * Modification à apporter à la BD
 * ALTER TABLE  `Utilisateurs` ADD  `pidParrain` INT( 5 ) NULL DEFAULT NULL;
 *
 * et à irpg.conf :
 *
 * [mod_parrainage]
 * actif = "1"
 * lvlBonus = "40"
 * pctBonus = "5"
 *
 */

/**
 * Module mod_parrainage
 * Gères la fonctionnalité de parrainage sur le bot.
 *
 * @author Homer
 * @author cedricpc
 * @created 18 avril 2010
 */
class parrainage
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
    var $name;    //Nom du module
    var $version; //Version du module
    var $desc;    //Description du module
    var $depend;  //Modules dont nous sommes dépendants

    //Variables supplémentaires
    var $actif;    //Si la fonctionalité de parainage est active.
    var $lvlBonus; //Le level requis par le joueur invité avant de donner le bonus au parrain.
    var $pctBonus; //Le % du TTL retiré au parrain.
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////

    function loadModule()
    {
        //Constructeur; initialisateur du module
        //S'éxécute lors du (re)chargement du bot ou d'un REHASH
        global $irc, $irpg, $db;

        /* Renseignement des variables importantes */
        $this->name    = "mod_parrainage";
        $this->version = "0.1.0";
        $this->desc    = "Module gérant les fonctionalités de parrainage.";
        $this->depend  = array("idle/1.0.0");

        //Recherche de dépendances
        if (!$irpg->checkDepd($this->depend)) {
            die("$this->name: dépendance non résolue\n");
        }

        //Validation du fichier de configuration spécifique au module
        $cfgKeys    = array("actif", "lvlBonus", "pctBonus");
        $cfgKeysOpt = array();

        if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
            die ($this->name . ": Vérifiez votre fichier de configuration.\n");
        }

        $this->actif    = $irpg->readConfig($this->name, "actif");
        $this->lvlBonus = $irpg->readConfig($this->name, "lvlBonus");
        $this->pctBonus = $irpg->readConfig($this->name, "pctBonus");
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
    }

///////////////////////////////////////////////////////////////

    function onPrivmsgPrive($nick, $user, $host, $message)
    {
        global $irc, $irpg, $db;

        //Implantation des commandes de base
        $message = trim(str_replace("\n", "", $message));
        $message = explode(" ", $message);
        $nb = count($message) - 1;

        switch (strtoupper($message[0])) {
        case "REGISTER2":
            //Création d'un compte sur le bot à l'aide d'un parrain
            if ($nb == 4) {
                $this->cmdRegister2($nick, $message[1], $message[2], $message[3], $message[4]);
            } else {
                $irc->notice($nick, "Syntaxe incorrecte. Syntaxe : "
                    . "REGISTER2 <utilisateur> <mot de passe> <courriel> <parrain>");
            }
            break;
        }
    }

///////////////////////////////////////////////////////////////

    function onNoticeCanal($nick, $user, $host, $message)
    {
        global $irc, $irpg, $db;
    }

///////////////////////////////////////////////////////////////

    function onNoticePrive($nick, $user, $host, $message)
    {
        global $irc, $irpg, $db;
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
    }

///////////////////////////////////////////////////////////////

    function onNick($nick, $user, $host, $newnick)
    {
        global $irc, $irpg, $db;
    }

///////////////////////////////////////////////////////////////

    function onKick($nick, $user, $host, $channel, $nickkicked)
    {
        global $irc, $irpg, $db;
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

/* Fonctions reliés aux commandes reçues par le bot */

    //TODO: il serait préférable de laisser mod_core créer le compte et de gérer le parrain
    // suite à la réception d'un signal qu'un nouveau compte a été créé.
    function cmdRegister2($nick, $username, $password, $email, $parrain)
    {
        global $irc, $irpg, $db;
        /* cmdREGISTER2 : crée un compte dans la base de données */

        // on vérififie si le module est actif
        if ($this->actif != "1") {
            $irc->notice($nick, "Désolé, la fonctionalité de parrainage n'est pas en fonction.");
            return false;
        }

        //On vérifie si l'utilisateur est sur le canal
        if (!$irc->isOn($irc->home, $nick)) {
            $irc->notice($nick, "Désolé, vous devez être sur \002$irc->home\002 pour vous enregistrer.");
            return false;
        } elseif (strlen($username) > 30) { //Validation du nom d'utilisateur
            $irc->notice($nick, "Désolé, votre nom d'utilisateur est trop long. "
                . "La limite autorisée est de \00230\002 caractères.");
            return false;
        } elseif (!preg_match('/[a-z0-9_-]+$/i', $username)) {
            $irc->notice($nick, "Désolé, votre nom d'utilisateur contient des caractères interdits. "
                . "Seuls les caractères \002alphanumériques\002, le \002tiret\002 et la \002barre de "
                . "soulignement\002 sont autorisés.");
            return false;
        } elseif ((strtoupper($username) == "IRPG") || (strtoupper($username) == "EIRPG")) {
            $irc->notice($nick, "Désolé, ce nom d'utilisateur est réservé.");
            return false;
        } else {
            //On vérifie que le nom n'existe pas déjà
            $table = $db->prefix . "Utilisateurs";
            $r = $db->req("SELECT Username FROM $table WHERE Username='$username'");
            if (mysql_num_rows($r) != 0) {
                $irc->notice($nick, "Désolé, ce nom d'utilisateur existe déjà. Veuillez en choisir un autre.");
                return false;
            }
        }

        //Encryption du mot de passe
        $password = md5($password);

        //Validation de l'adresse de courriel
        if (!$irpg->mod['core']->validerMail($email)) {
            $irc->notice($nick, "Désolé, votre adresse de courriel n'est pas valide.");
            return false;
        }

        // on vérifie que le parrain existe
        if (!$pid = $irpg->getPIDByPerso($parrain)) {
            $irc->notice($nick, "Votre parrain n'a pas été trouvé. Vous devez utiliser son nom de personnage IRPG.");
            return false;
        }

        //Requête SQL maintenant :)
        $table = $db->prefix . "Utilisateurs";
        $db->req("INSERT INTO $table (`Username`, `Password`, `Email`, `Created`, `pidParrain`)
                  VALUES ('$username', '$password', '$email', NOW(), '$pid')");
        $irc->notice($nick, "Votre compte \002$username\002 a été créé avec succès !");
        $irc->notice($nick, "Vous pouvez à présent vous authentifier à l'aide de la commande \002LOGIN\002 "
            . "puis ensuite créer votre premier personnage à l'aide de la commande \002CREATE\002.");
        $irc->privmsg($irc->home, "Bienvenue à notre nouveau joueur $username invité par $parrain, connecté "
            . "sous le pseudo $nick !");
    }

///////////////////////////////////////////////////////////////

    function modIdle_onLvlUp($nick, $uid, $pid, $level, $next) {
        global $irc, $irpg, $db;

        $tbPerso = '`' . $db->prefix . 'Personnages`';

        if (($level == $this->lvlBonus) && ($ppid = $this->getParrainPIDByUID($uid))) {
            // on donne le bonus au parrain
            if (!$parrain = $this->getPersoByParrainPID($ppid)) {
                //parrain non trouvé
                return false;
            }

            $pPerso = $parrain['Nom'];
            $pLevel = $parrain['Level'];
            $pNext  = $parrain['Next'];

            $bonus  = round(($this->pctBonus / 100) * $pNext, 0);
            $ttl    = $pNext - $bonus;

            $db->req('UPDATE ' . $tbPerso . ' SET `Next` = `Next` - ' . $bonus . ' WHERE `Id_Personnages` = ' . $ppid);

            $perso = $irpg->getNomPersoByPID($pid);
            $irc->privmsg($irc->home, $pPerso . ', le parrain de ' . $perso . ' est récompensé par le retrait de 5% '
                . 'de son TTL. Ce bonus l\'accélère de ' . $irpg->convSecondes($bonus) . ' ! Prochain niveau dans '
                . $irpg->convSecondes($ttl) . '.');
        }
    }

/////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////

/* Fonctions diverses */

    function getParrainPIDByUID($uid) {
        global $db;

        $req = $db->getRows('SELECT `pidParrain` FROM `' . $db->prefix . 'Utilisateurs` WHERE `Id_Utilisateurs` = '
             . intval($uid));
        return (count($req) > 0 ? $req[0]['pidParrain'] : false);
    }

////////////////////////////////////////////////////////////////

    function getPersoByParrainPID($ppid) {
        global $db;

        $req = $db->getRows('SELECT * FROM `' . $db->prefix . 'Personnages` WHERE `ID_Personnages` = '
             . intval($ppid));
        return (count($req) > 0 ? $req[0] : false);
    }

////////////////////////////////////////////////////////////////
}
?>
