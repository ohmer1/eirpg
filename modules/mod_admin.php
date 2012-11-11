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

/**
 * Module mod_admin
 * Commandes d'administration'
 *
 * @author cedricpc
 * @created   Jeudi    22 Mars      2007
 * @modified  Dimanche 16 Septembre 2012 @ 01:40 (CEST)
 */
class admin
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
    var $name;    //Nom du module
    var $version; //Version du module
    var $desc;    //Description du module
    var $depend;  //Modules dont nous sommes dépendants

    //Variables supplémentaires

//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////

    function loadModule()
    {
        //Constructeur; initialisateur du module
        //S'éxécute lors du (re)chargement du bot ou d'un REHASH
        global $irc, $irpg, $db;

        /* Renseignement des variables importantes */
        $this->name    = "mod_admin";
        $this->version = "0.3.3";
        $this->desc    = "Commandes d'administration'";
        $this->depend  = array("core/0.5.0");

        //Recherche de dépendances
        if (!$irpg->checkDepd($this->depend)) {
            die("$this->name: dépendance non résolue.\n");
        }

        //Validation du fichier de configuration spécifique au module
        $cfgKeys    = array();
        $cfgKeysOpt = array();

        //Validation du fichier de configuration spécifique au module
        if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
            die("$this->name: Vérifiez votre fichier de configuration.\n");
        }
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

        $message = trim(str_replace("\n", "", $message));
        $message = explode(" ", $message);
        $nb = count($message) - 1;
        $uid = $irpg->getUsernameByNick($nick, true);

        switch (strtoupper($message[0])) {
        case "DIE":
            //Arrête le bot
            if ($irpg->getAdminLvl($uid[1]) >= 9) {
                $raison = implode(" ", array_slice($message, 1));
                $this->cmdDie($nick, $uid[0], $raison);
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "RESTART":
            //Redémarre le bot
            if ($irpg->getAdminLvl($uid[1]) >= 9) {
                $raison = implode(" ", array_slice($message, 1));
                $this->cmdRestart($nick, $uid[0], $raison);
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "CHGPASS":
            //Change le mot de passe d'un utilisateur
            if ($irpg->getAdminLvl($uid[1]) >= 5) {
                if ($nb < 2) {
                    $irc->notice($nick, "Syntaxe : CHGPASS <utilisateur> <nouveau_mot_de_passe>");
                } else {
                    $this->cmdChgPass($nick, $message[1], $message[2]);
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "CHGCLASS":
            //Change la classe d'un personnage
            if ($irpg->getAdminLvl($uid[1]) >= 5) {
                if ($nb < 2) {
                    $irc->notice($nick, "Syntaxe : CHGCLASS <personnage> <nouvelle classe>");
                } else {
                    $classe = implode(" ", array_slice($message, 2));
                    $this->cmdChgClass($nick, $message[1], $classe);
                }
            }
            break;

        case "CHGPERSO":
            //Change le nom d'un personnage
            if ($irpg->getAdminLvl($uid[1]) >= 5) {
                if ($nb < 2) {
                    $irc->notice($nick, "Syntaxe : CHGPERSO <personnage> <nouveau_personnage>");
                } else {
                    $this->cmdChgPerso($nick, $message[1], $message[2]);
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "CHGUSER":
            //Change le nom d'un utilisateur
            if ($irpg->getAdminLvl($uid[1]) >= 5) {
                if ($nb < 2) {
                    $irc->notice($nick, "Syntaxe : CHGUSER <utilisateur> <nouvel_utilisateur>");
                } else {
                    $this->cmdChgUser($nick, $message[1], $message[2]);
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "DELPERSO":
            //Supprime un personnage
            if ($irpg->getAdminLvl($uid[1]) >= 7) {
                if ($nb < 1) {
                    $irc->notice($nick, "Syntaxe : DELPERSO <personnage>");
                } else {
                    $this->cmdDelPerso($nick, $message[1]);
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "DELUSER":
            //Supprime un utilisateur
            if ($irpg->getAdminLvl($uid[1]) >= 7) {
                if ($nb < 1) {
                    $irc->notice($nick, "Syntaxe : DELUSER <utilisateur>");
                } else {
                    $this->cmdDelUser($nick, $message[1]);
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "DELADMIN":
            //Supprime un administrateur
            if ($irpg->getAdminLvl($uid[1]) >= 9) {
                if ($nb < 1) {
                    $irc->notice($nick, "Syntaxe : DELADMIN <administrateur>");
                } else {
                    $this->cmdDelAdmin($nick, $message[1]);
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "ADDADMIN":
            //Donne des droits d'administrateur à un utilisateur
            if ($irpg->getAdminLvl($uid[1]) >= 9) {
                if (($nb < 2) || (!is_numeric($message[2]))) {
                    $irc->notice($nick, "Syntaxe : ADDADMIN <utilisateur> <niveau_en_chiffre>");
                } else {
                    $this->cmdAddAdmin($nick, $message[1], $message[2]);
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "PULL":
            //Ajoute du temps au TTL d'un personnage
            if ($irpg->getAdminLvl($uid[1]) >= 5) {
                $temps    = ($nb < 2 ? null : preg_replace('/%+$/', '', $message[2]));
                $pourcent = ($nb < 2 ? false : strpos($message[2] . ($nb > 2 ? $message[3] : ''), '%') !== false);

                if (($nb < 2) || !is_numeric($temps)) {
                    $irc->notice($nick, 'Syntaxe : PULL <personnage> <temps_en_secondes|pourcentage %>');
                } elseif ($temps > 0) {
                    $this->cmdPull($nick, $message[1], $temps, $pourcent);
                } else {
                    $irc->notice($nick, 'Le ' . ($pourcent ? 'pourcentage' : 'temps')
                        . ' doit être strictement supérieur à 0.');
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "PUSH":
            //Enlève du temps au TTL d'un personnage
            if ($irpg->getAdminLvl($uid[1]) >= 5) {
                $temps    = ($nb < 2 ? null : preg_replace('/%+$/', '', $message[2]));
                $pourcent = ($nb < 2 ? false : strpos($message[2] . ($nb > 2 ? $message[3] : ''), '%') !== false);

                if (($nb < 2) || !is_numeric($temps)) {
                    $irc->notice($nick, 'Syntaxe : PUSH <personnage> <temps_en_secondes|pourcentage %>');
                } elseif ($temps > 0) {
                    $this->cmdPush($nick, $message[1], $temps, $pourcent);
                } else {
                    $irc->notice($nick, 'Le ' . ($pourcent ? 'pourcentage' : 'temps')
                        . ' doit être strictement supérieur à 0.');
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
            break;

        case "SAY":
            //Envoi un message sur le canal
            if ($irpg->getAdminLvl($uid[1]) >= 5) {
                if ($nb < 1) {
                    $irc->notice($nick, "Syntaxe : SAY <le message>");
                } else {
                    $msg = implode(" ", array_slice($message, 1));
                    $this->cmdSay($nick, $msg);
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
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

    function cmdDie($nick, $user, $raison)
    {
        //TODO: exécuter la méthode unload() de chaque module avant de shutdown le bot
        global $irc, $db, $irpg;

        $raison = stripslashes($raison);
        $irpg->Log(
            null, "ADMIN", "0", "$user ($nick) a utilisé la commande SHUTDOWN" . ($raison != Null ? " ($raison)" : "")
        );
        $irc->deconnexion("Arrêt du bot demandé par $nick ($user)" . ($raison != Null ? " : $raison" : ""));
        $db->deconnexion();
    }

///////////////////////////////////////////////////////////////

    function cmdRestart($nick, $user, $raison)
    {
        //TODO: Améliorer - ou pas - la gestion du redémarrage et exécuter la méthode unload() de chaque module
        //      avant l'arrêt du bot
        global $irc, $db, $irpg;

        $raison = stripslashes($raison);
        $irpg->Log(
            null, "ADMIN", "0", "$user ($nick) a utilisé la commande RESTART" . ($raison != Null ? " ($raison)" : "")
        );
        $deconnexionIrc = $irc->deconnexion(
            "Redémarrage du bot demandé par $nick ($user)" . ($raison != Null ? " : $raison" : "")
        );
        $db->deconnexion();

        if ($deconnexionIrc) {
            exec('./irpg.php > /dev/null 2>&1 &');
        }
    }

///////////////////////////////////////////////////////////////

    function cmdChgPass($nick, $user, $newPass)
    {
        global $irpg, $db, $irc;

        if (!$irpg->userExist($user)) {
            $irc->notice($nick, "L'utilisateur que vous avez spécifié n'existe pas !");
            return;
        }

        $table = $db->prefix . "Utilisateurs";
        $newPassHash = md5($newPass);
        $db->req("UPDATE `$table` SET `Password` = '$newPassHash' WHERE `Username` = '$user'");
        if ($user == $irpg->getUsernameByNick($nick)) {
            $irc->notice($nick, "Votre mot de passe a bien été changé.");
        } else {
            $irc->notice($nick, "Le mot de passe de $user a bien été changé.");
        }
        $irpg->Log(
            null, "ADMIN", "0", "$irpg->getUsernameByNick($nick) ($nick) a utilisé la commande CHGPASS ($user)"
        );
    }

///////////////////////////////////////////////////////////////

    function cmdChgClass($nick, $perso, $newClass)
    {
        global $irpg, $db, $irc;

        if (strlen($newClass) > 50) {
            $irc->notice($nick, "Désolé, la classe que vous voulez donner est trop longue. "
                . "La limite autorisée est de \00250\002 caractères.");
            return;
        }
        if (!$irpg->persoExist($perso)) {
            $irc->notice($nick,"Le personnage que vous avez spécifié n'existe pas !");
            return;
        }

        $table = $db->prefix . "Personnages";
        $db->req("UPDATE `$table` SET `Class` = '$newClass' WHERE `Nom` = '$perso'");
        $irc->notice($nick, "La classe de $perso a bien été changée.");
        $irpg->Log(null, "ADMIN", "0",
            "$irpg->getUsernameByNick($nick) ($nick) a utilisé la commande CHGCLASS ($perso -> $newClass)"
        );
    }

///////////////////////////////////////////////////////////////

    function cmdChgPerso($nick, $perso, $newPerso)
    {
        global $irpg, $db, $irc;

        if (strlen($newPerso) > 30) {
            $irc->notice($nick, "Désolé, le nom du personnage que vous voulez donner est trop long. "
                . "La limite autorisée est de \00230\002 caractères.");
            return;
        }
        if (!$irpg->persoExist($perso)) {
            $irc->notice($nick, "Le personnage que vous avez spécifié n'existe pas !");
            return;
        }
        if ($irpg->persoExist($newPerso)) {
            $irc->notice($nick, "Le nom du personnage que vous voulez donner existe déjà !");
            return;
        }
        if ($nick != $irpg->getNickByUID($irpg->getUIDByPID($irpg->getPIDByPerso($perso)))) {
            if ($irpg->getAdminLvl($irpg->getUIDByUsername($irpg->getUsernameByNick($nick))) <= $irpg->getAdminLvl(
                $irpg->getUIDByPID($irpg->getPIDByPerso($perso))
            )) {
                $irc->notice($nick, "Vous ne pouvez pas changer le nom du personnage d'un administrateur "
                    . "qui a un niveau supérieur ou égal au vôtre !");
                return;
            }
            $persoIsMine = false;
        } else {
            $persoIsMine = true;
        }

        $table = $db->prefix . "Personnages";
        $db->req("UPDATE `$table` SET `Nom` = '$newPerso' WHERE `Nom` = '$perso'");
        $irc->notice($nick, "Le nom de" . ($persoIsMine ? " votre personnage" : "") . " $perso a bien été changé.");
        $irpg->Log(null, "ADMIN", "0",
            "$irpg->getUsernameByNick($nick) ($nick) a utilisé la commande CHGPERSO ($perso -> $newPerso)"
        );
        //TODO: donner une penalité pour mettre à jour tous les modules.
    }

///////////////////////////////////////////////////////////////

    function cmdChgUser($nick, $user, $newUser)
    {
        global $irpg, $db, $irc;

        if ((strtoupper($newUser) == 'IRPG') && (strtoupper($newUser) == 'EIRPG')) {
            $irc->notice($nick, "Désolé, ce nom d'utilisateur est reservé ! Veuillez en choisir un autre.");
            return;
        }
        if (strlen($newUser) > 30) {
            $irc->notice($nick, "Désolé, le nom d'utilisateur que vous voulez donner est trop long. "
                . "La limite autorisée est de \00230\002 caractères.");
            return;
        }
        if (!$irpg->userExist($user)) {
            $irc->notice($nick, "L'utilisateur que vous avez spécifié n'existe pas !");
            return;
        }
        if ($irpg->userExist($newUser)) {
            $irc->notice($nick, "Le nom d'utilisateur que vous voulez donner existe déjà !");
            return;
        }
        if ($user != $irpg->getUsernameByNick($nick)) {
            if ($irpg->getAdminLvl($irpg->getUIDByUsername($irpg->getUsernameByNick($nick))) <= $irpg->getAdminLvl(
                $irpg->getUIDByUsername($user)
            )) {
                $irc->notice($nick, "Vous ne pouvez pas changer le nom d'un administrateur "
                    . "qui a un niveau supérieur ou égal au vôtre !");
                return;
            }
            $userIsMe = false;
        } else {
            $userIsMe = true;
        }

        $table = $db->prefix . "Utilisateurs";
        $db->req("UPDATE `$table` SET `Username` = '$newUser' WHERE `Username` = '$user'");
        if ($userIsMe) {
            $irc->notice($nick, "Votre nom d'utilisteur a bien été changé.");
        } else {
            $irc->notice($nick, "Le nom d'utilisateur de $user a bien été changé.");
        }
        $irpg->Log(null, "ADMIN", "0",
            "$irpg->getUsernameByNick($nick) ($nick) a utilisé la commande CHGUSER ($user -> $newUser)"
        );
        //TODO: donner une penalité pour mettre à jour tous les modules.
    }

///////////////////////////////////////////////////////////

    function cmdDelPerso($nick, $perso)
    {
        global $irpg, $db, $irc;

        if (!$irpg->persoExist($perso)) {
            $irc->notice($nick, "Le personnage que vous avez spécifié n'existe pas !");
            return;
        }
        if ($nick != $irpg->getNickByUID($irpg->getUIDByPID($irpg->getPIDByPerso($perso)))) {
            if ($irpg->getAdminLvl($irpg->getUIDByUsername($irpg->getUsernameByNick($nick))) <= $irpg->getAdminLvl(
                $irpg->getUIDByPID($irpg->getPIDByPerso($perso))
            )) {
                $irc->notice($nick, "Vous ne pouvez pas supprimer le personnage d'un administrateur "
                    . "qui a un niveau supérieur ou égal au vôtre !");
                return;
            }
            $persoIsMine = false;
        } else {
            $persoIsMine = true;
        }

        $table = $db->prefix . "Personnages";
        $db->req("DELETE FROM `$table` WHERE `Nom` = '$perso'");
        $irc->notice($nick, ($persoIsMine ? "Votre" : "Le") . " personnage $perso a bien été supprimé.");
        $irpg->Log(
            null, "ADMIN", "0", "$irpg->getUsernameByNick($nick) ($nick) a utilisé la commande DELPERSO ($perso)"
        );
        //TODO: mettre une penalité tel un kick à l'utilisateur qui est supprimé
        //      pour mettre à jour tous les autres modules
    }

///////////////////////////////////////////////////////////////

    function cmdDelUser($nick, $user)
    {
        global $irpg, $db, $irc;

        if (!$irpg->userExist($user)) {
            $irc->notice($nick,"L'utilisateur que vous avez spécifié n'existe pas !");
            return;
        }
        if ($user == $irpg->getUsernameByNick($nick)) {
            $irc->notice($nick, "Vous ne pouvez pas supprimer votre propre compte !");
            return;
        }
        if ($irpg->getAdminLvl($irpg->getUIDByUsername($irpg->getUsernameByNick($nick))) <= $irpg->getAdminLvl(
            $irpg->getUIDByUsername($user)
        )) {
            $irc->notice($nick, "Vous ne pouvez pas supprimer le compte d'un administrateur "
                . "qui a un niveau supérieur ou égal au vôtre !");
            return;
        }

        $table = $db->prefix . "Utilisateurs";
        $db->req("DELETE FROM `$table` WHERE `Username` = '$user'");
        $irc->notice($nick, "L'utilisateur $user a bien été supprimé.");
        $irpg->Log(
            null, "ADMIN", "0", "$irpg->getUsernameByNick($nick) ($nick) a utilisé la commande DELUSER ($user)"
        );
        //TODO: mettre une penalité tel un kick à l'utilisateur qui est supprimé
        //      pour mettre à jour tous les autres modules
    }

///////////////////////////////////////////////////////////////

    function cmdDelAdmin($nick, $user)
    {
        global $irpg, $db, $irc;

        if (!$irpg->userExist($user)) {
            $irc->notice($nick,"L'utilisateur que vous avez spécifié n'existe pas !");
            return;
        }
        if ($user == $irpg->getUsernameByNick($nick)) {
            $irc->notice($nick, "Vous ne pouvez pas supprimer vos privilèges d'administrateur !");
            return;
        }
        if (($userNiveau = $irpg->getAdminLvl($irpg->getUIDByUsername($user))) == 0) {
            $irc->notice($nick, "L'utilisateur que vous avez spécifié n'est pas administrateur !");
            return;
        }
        if ($irpg->getAdminLvl($irpg->getUIDByUsername($irpg->getUsernameByNick($nick))) <= $userNiveau) {
            $irc->notice($nick, "Vous ne pouvez pas supprimer les droits d'un administrateur "
                . "qui a un niveau supérieur ou égal au vôtre !");
            return;
        }

        $table = $db->prefix . "Utilisateurs";
        $db->req("UPDATE `$table` SET `Admin` = '0' WHERE `Username` = '$user'");
        $irc->notice($nick, "Les privilèges d'administrateur de $user ont bien été supprimés.");
        $irpg->Log(
            null, "ADMIN", "0", "$irpg->getUsernameByNick($nick) ($nick) a utilisé la commande DELADMIN ($user)"
        );
        //TODO: Avertir l'utilisateur de la démission de ses fonctions.
    }

///////////////////////////////////////////////////////////////

    function cmdAddAdmin($nick, $user, $niveau)
    {
        global $irpg, $db, $irc;

        if ($niveau <= 0) {
            $irc->notice($nick, "Le niveau d'administration doit être plus grand que 0 ! "
                . "Utilisez la commande DELADMIN pour retirer les privilèges d'un aministrateur.");
            return;
        }
        if (!$irpg->userExist($user)) {
            $irc->notice($nick, "L'utilisateur que vous avez spécifié n'existe pas !");
            return;
        }
        if ($user == $irpg->getUsernameByNick($nick)) {
            $irc->notice($nick, "Vous ne pouvez pas changer votre propre niveau d'administration !");
            return;
        }
        if (($nickNiveau = $irpg->getAdminLvl($irpg->getUIDByUsername($irpg->getUsernameByNick(
            $nick
        )))) <= ($userNiveau = $irpg->getAdminLvl($irpg->getUIDByUsername($user)))) {
            $irc->notice($nick, "Vous ne pouvez pas modifier les droits d'un administrateur "
                . "qui a un niveau supérieur ou égal au vôtre !");
            return;
        }
        if ($nickNiveau <= $niveau) {
            $irc->notice($nick, "Vous ne pouvez pas donner un niveau d'administration supérieur ou égal au vôtre !");
            return;
        }
        if ($userNiveau == $niveau) {
            $irc->notice($nick, "$user est déjà un administrateur de niveau $niveau !");
            return;
        }

        $table = $db->prefix . "Utilisateurs";
        $db->req("UPDATE `$table` SET `Admin` = '$niveau' WHERE `Username` = '$user'");
        $irpg->Log(null, "ADMIN", "0",
            "$irpg->getUsernameByNick($nick) ($nick) a utilisé la commande ADDADMIN ($user -> $niveau)"
        );
        if ($userNiveau > 0) {
            $irc->notice($nick, "Les privilèges d'administrateur de $user ont bien été modifiés.");
        } else {
            $irc->notice($nick, "Les privilèges d'administrateur ont bien été donnés à $user.");
        }
        //TODO: Avertir l'utilisateur de ses nouvelles fonctions.
    }

///////////////////////////////////////////////////////////////

    function cmdPush($nick, $perso, $temps, $pourcent = false)
    {
        global $irpg, $db, $irc;

        if (!$irpg->persoExist($perso)) {
            $irc->notice($nick, 'Le personnage que vous avez spécifié n\'existe pas !');
            return;
        }

        $table = $db->prefix . 'Personnages';

        $data  = $db->getRows('SELECT `Id_Personnages`, `Util_Id`, `Nom`, `Level`, `Class`, `Next`
                               FROM `' . $table . '` WHERE `Nom` = \'' . $perso . '\'');
        $next  = $data[0]['Next'];

        if ($pourcent) {
            $pourcent = min((float) $temps, 100);
            $temps    = $next * $pourcent / 100;
        } else {
            $temps = ($next > $temps ? (int) $temps : $next);
        }

        $db->req('UPDATE `' . $table . '` SET `Next` = `Next` - ' . $temps . ' WHERE `Nom` = \'' . $perso . '\'');
        $irc->notice($nick, 'Le temps de ' . $perso . ' a bien été avancé de '
            . ($pourcent ? $pourcent . ' % soit ' : '') . $irpg->convSecondes($temps) . '.');
        $irpg->Log(null, 'ADMIN', '0', $irpg->getUsernameByNick($nick) . ' (' . $nick
            . ') a utilisé la commande PUSH (' . $perso . ' -> ' . ($pourcent ? $pourcent . ' % -> ' : '')
            . $temps . ')');
        if ($temps >= $next) {
            $irc->privmsg($irc->home, $nick . ' a accéléré la progression de ' . $perso . ' de '
                . ($pourcent ? $pourcent . ' % soit ' : '') . $irpg->convSecondes($temps) . '.');
            $data[0]['Next'] = 0;
            $irpg->mod['idle']->cmdLvlUp($data[0]['Id_Personnages'], array($data[0]));
        } else {
            $irc->privmsg($irc->home, $nick . ' a accéléré la progression de ' . $perso . ' de '
                . ($pourcent ? $pourcent . ' % soit ' : '') . $irpg->convSecondes($temps) . '. Prochain niveau dans '
                . $irpg->convSecondes($next - $temps) . '.');
        }
    }

///////////////////////////////////////////////////////////////

    function cmdPull($nick, $perso, $temps, $pourcent = false)
    {
        global $irpg, $db, $irc;

        if (!$irpg->persoExist($perso)) {
            $irc->notice($nick, 'Le personnage que vous avez spécifié n\'existe pas !');
            return;
        }

        $table = $db->prefix . 'Personnages';

        if ($pourcent) {
            $data  = $db->getRows('SELECT `Id_Personnages`, `Util_Id`, `Nom`, `Level`, `Class`, `Next` FROM `'
                   . $table . '` WHERE `Nom` = \'' . $perso . '\'');
            $next  = $data[0]['Next'];

            $pourcent = (float) $temps;
            $temps    = $next * $pourcent / 100;
        } else {
            $temps = (int) $temps;
        }

        $db->req('UPDATE `' . $table . '` SET `Next` = `Next` + ' . $temps . ' WHERE `Nom` = \'' . $perso . '\'');
        $irc->notice($nick, 'Le temps de ' . $perso . ' a bien été reculé de '
            . ($pourcent ? $pourcent . ' % soit ' : '') . $irpg->convSecondes($temps) . '.');
        $irpg->Log(null, 'ADMIN', '0', $irpg->getUsernameByNick($nick) . ' (' . $nick
            . ') a utilisé la commande PULL (' . $perso . ' -> ' . ($pourcent ? $pourcent . ' % -> ' : '')
            . $temps . ')');

        $data = $db->getRows('SELECT `Next` FROM `' . $table . '` WHERE `Nom` = \'' . $perso . '\'');
        $irc->privmsg($irc->home, $nick . ' a ralenti la progression de ' . $perso . ' vers son prochain niveau de '
            . ($pourcent ? $pourcent . ' % soit ' : '') . $irpg->convSecondes($temps) . '. Prochain niveau dans '
            . $irpg->convSecondes($data[0]['Next']) . '.');
    }

///////////////////////////////////////////////////////////////

    function cmdSay($nick, $message)
    {
        global $irpg, $db, $irc;

        $irc->privmsg($irc->home, stripslashes($message));
    }

///////////////////////////////////////////////////////////////
}
?>
