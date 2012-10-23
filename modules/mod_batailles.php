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
 * Module mod_batailles.php
 * Gestion des batailles dans le jeu
 *
 * @author    Homer
 * @author    cedricpc
 * @created   Samedi   13 Mai       2006
 * @modified  Mercredi 24 Octobre   2012 @ 01:05 (CEST)
 */
class batailles
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
        $this->name    = "mod_batailles";
        $this->version = "0.5.9";
        $this->desc    = "Module de gestion des batailles";
        $this->depend  = array("core/0.5.0", "idle/1.0.0", "objets/0.9.0");

        //Recherche de dépendances
        if (!$irpg->checkDepd($this->depend)) {
            die("$this->name: dépendance non résolue\n");
        }

        //Validation du fichier de configuration spécifique au module
        $cfgKeys    = array();
        $cfgKeysOpt = array();

        if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
            die("$this->name: Vérifiez votre fichier de configuration.\n");
        }

        //Initialisation des paramètres du fichier de configuration
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

        $message = explode(' ', trim(str_replace("\n", '', $message)));
        $nb = count($message) - 1;

        switch (strtoupper($message[0])) {
        case 'CHALLENGE':
        case 'COMBAT':
            $username = array_search($nick, $irpg->mod["core"]->users);
            if (is_string($username)) {
                $tblPerso = $db->prefix . "Personnages";
                $tblUtil  = $db->prefix . "Utilisateurs";

                $perso = $db->getRows("SELECT COUNT(*) AS `nb`, `Nom` FROM `{$tblPerso}` LEFT JOIN `{$tblUtil}`
                                       ON `Util_Id` = `Id_Utilisateurs` WHERE `Username` = '{$username}'");
                if ($perso[0]['nb'] == 1) {
                    $this->cmdBataille($nick, $perso[0]['Nom'], ($nb < 1 ? null : $message[1]), true);
                    break;
                }
            }

            if ($nb < 1) {
                $irc->notice($nick, 'Syntaxe : COMBAT <personnage> [adversaire]');
            }
            else {
                $this->cmdBataille($nick, $message[1], ($nb < 2 ? null : $message[2]));
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
        global $db;

        $tblPerso = $db->prefix . "Personnages";
        $tblIRC   = $db->prefix . "IRC";

        //On diminue de 15 secondes le temps d'attente pour les combats manuels aux personnages en ligne.
        $db->req("UPDATE `{$tblPerso}` AS p LEFT JOIN `{$tblIRC}` AS i ON p.`Id_Personnages` = i.`Pers_Id`
                  SET p.`ChallengeNext` = IF(p.`ChallengeNext` < 15, 0, p.`ChallengeNext` - 15)
                  WHERE i.`Pers_Id` IS NOT NULL");
    }

///////////////////////////////////////////////////////////////

    function modIdle_onLvlUp($nick, $uid, $pid, $level, $next)
    {
        // À chaque monté de niveau,
        // .. il y a 25% de chance d'avoir une bataille lorsque niveau < 10
        // .. il y a 100% de chance d'avoir une bataille lorsque niveau >= 10
        if ($level >= 10) {
            $this->batailleDuel($pid, $level);
        } else {
            // 1 chance sur 4
            if (rand(1, 4) == 1) {
                $this->batailleDuel($pid, $level);
            }
        }
    }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

    function batailleDuel($pid, $level)
    {
        global $db, $irc, $irpg;

        $tIRC   = $db->prefix . "IRC";
        $tPerso = $db->prefix . "Personnages";
        $perso  = $irpg->getNomPersoByPID($pid);
        $uid    = $irpg->getUIDByPID($pid);
        $level2 = $level + 1;

        $ttl = $db->getRows("SELECT Next FROM $tPerso WHERE Id_Personnages='$pid'");
        $ttl = $ttl[0]["Next"];

        // Sélectionne un autre joueur en ligne pour duel
        $q = "SELECT Pers_Id FROM $tIRC WHERE Pers_Id Not
              IN (SELECT Id_Personnages FROM $tPerso WHERE Util_Id='$uid') And Not IsNULL(Pers_Id)
              ORDER BY RAND() LIMIT 0,1";
        if ($db->nbLignes($q) == 0) {
                return false;
        } else {
            $res      = $db->getRows($q);
            $pidOpp   = $res[0]["Pers_Id"];
            $opposant = $irpg->getNomPersoByPID($pidOpp);

            $levelOpp = $db->getRows("SELECT Level FROM $tPerso WHERE Id_Personnages='$pidOpp'");
            $levelOpp = $levelOpp[0]["Level"];

            // Calcul des sommes
            $somme    = $this->calcSomme($pid);
            $sommeOpp = $this->calcSomme($pidOpp);

            // Nombre aléatoire entre 0 et la somme
            $rand    = rand(0, $somme);
            $randOpp = rand(0, $sommeOpp);

            if ($rand > $randOpp) {
                //gagné..
                $mod = $levelOpp / 4;

                if ($mod < 7) {
                    $mod = 7;
                }

                $mod = round(($mod / 100) * $ttl, 0);
                $cmod = $irpg->convSecondes($mod);

                $db->req("UPDATE $tPerso SET Next=Next-$mod WHERE Id_Personnages='$pid'");
                $cnext = $db->getRows("SELECT Next FROM $tPerso WHERE Id_Personnages='$pid'");
                $cnext = $irpg->convSecondes($cnext[0]["Next"]);

                $irpg->Log($pid, "DUEL_AUTO", "GAGNÉ", "-$mod");

                $irc->privmsg($irc->home, "$perso [$rand/$somme] a provoqué en duel $opposant "
                    . "[$randOpp/$sommeOpp] et a gagné ! Cette victoire lui donne droit à un bonus de $cmod "
                    . "avant d'accéder au niveau $level2. Prochain niveau dans $cnext.");
            } elseif ($rand < $randOpp) {
                //perdu..
                $mod = $levelOpp / 7;

                if ($mod < 7) {
                    $mod = 7;
                }

                $mod = round(($mod / 100) * $ttl, 0);
                $cmod = $irpg->convSecondes($mod);

                $db->req("UPDATE $tPerso SET Next=Next+$mod WHERE Id_Personnages='$pid'");
                $cnext = $db->getRows("SELECT Next FROM $tPerso WHERE Id_Personnages='$pid'");
                $cnext = $irpg->convSecondes($cnext[0]["Next"]);

                $irpg->Log($pid, "DUEL_AUTO", "PERDU", "$mod");

                $irc->privmsg($irc->home, "$perso [$rand/$somme] a provoqué en duel $opposant "
                    . "[$randOpp/$sommeOpp] et a perdu ! Cette défaite lui donne droit à une pénalité de $cmod "
                    . "avant d'accéder au niveau $level2. Prochain niveau dans $cnext.");
            } else {
                //match nul..
                $irpg->Log($pid, "DUEL_AUTO", "NUL", 0);
                $irc->privmsg($irc->home, "$perso [$rand/$somme] a provoqué en duel $opposant "
                    . "[$randOpp/$sommeOpp]. Match nul !");
            }
        }
    }

/////////////////////////////////////////////////////////

    /**
     * Lance un combat manuel à la demande du joueur.
     *
     * @author    cedricpc
     * @created   22 Avril 2010
     * @modified  23 Avril 2010
     *
     * @param string $nick      le pseudo de l'assaillant
     * @param string $perso     le personnage qui attaque
     * @param string $opposant  le personnage à attaquer
     *
     * @return void  ou false en cas de problème
     */
    function cmdBataille($nick, $perso, $opposant = null, $persoUnique = false) {
        global $db, $irc, $irpg;

        $tblPerso = $db->prefix . "Personnages";
        $tblIRC   = $db->prefix . "IRC";

        //Vérifie si l'opposant n'est pas l'attaquant.
        if (!empty($opposant) && ($perso == $opposant)) {
            $irc->notice($nick, "Désolé, votre personnage ne peut pas s'attaquer lui-même.");
            return false;
        }

        //Récupère les informations du personnage s'il existe.
        if (!$perso = current((array) $db->getRows("SELECT * FROM `{$tblPerso}` WHERE `Nom` = '{$perso}'"))) {
            $irc->notice($nick, "Désolé, ce personnage n'a pu être trouvé.");
            return false;
        }

        //Vérifie si le personnage appartient au joueur.
        list($user, $uid) = $irpg->getUsernameByNick($nick, true);
        if ($uid != $perso["Util_Id"]) {
            $irc->notice($nick, "Désolé, vous ne pouvez pas entâmer un combat avec un personnage qui ne vous "
                . "appartient pas.");
            return false;
        }

        $nom  = $perso["Nom"];
        $lvl  = $perso["Level"] + 1;
        $next = $perso["Next"];
        $pid  = $perso["Id_Personnages"];
        $nbChallenges  = $perso["ChallengeTimes"];
        $nextChallenge = $perso["ChallengeNext"];

        //Envoi des informations pour le premier combat du personnage.
        if (!$nbChallenges) {
            $irc->notice($nick, "Bienvenue dans le module de combats manuels. Avant de commencer les choses "
                . "sérieuses, voici quelques informations utiles à savoir.");
            $irc->notice($nick, "Bien que le nombre de combat ne soit pas limité, vous devrez attendre un certain "
                . "temps, qui augmente de manière exponentielle après chaque combat, avant de pouvoir effectuer un "
                . "nouveau combat.");
            $irc->notice($nick, "Le temps que votre personnage peut gagner, ou perdre, est proportionnel à la somme "
                . "des objets de son adversaire. Combattre un adversaire avec une plus grosse somme d'objets "
                . "permettera donc de gagner plus de temps qu'avec un adversaire ayant une somme d'objets plus "
                . "faible en cas de victoire, mais en fera aussi perdre d'avantage en cas de défaite.");
            $irc->notice($nick, "Ainsi, vous avez la possibilité de spécifier le personnage que vous souhaitez "
                . "voir votre personnage affronter. Si vous omettez de le préciser, son adversaire sera choisi "
                . "aléatoirement. Enfin, il est impératif d'indiquer votre personnage que vous souhaitez envoyer "
                . "à la bataille.");
            $irc->notice($nick, "La syntaxe de la commande est la suivante : COMBAT "
                . ($persoUnique ? "" : "<personnage> ") . "[adversaire]");

            $db->req("UPDATE `{$tblPerso}` SET `ChallengeTimes` = 1 WHERE `Id_Personnages` = '{$pid}'");

            return false;
        }

        //Vérifie si le temps d'attente avant le prochain combat est terminé.
        if ($nextChallenge > 0) {
            $irc->notice($nick, "Désolé, vous ne pouvez pas entreprendre de combats manuels pour le moment. Vous "
                . "devez encore attendre {$irpg->convSecondes($nextChallenge)} avant d'entâmer un nouveau combat.");
            return false;
        }

        //Selectionne aléatoirement un personnage à combattre s'il n'a pas été spécifié.
        if (empty($opposant)) {
            $q = "SELECT p.*, i.`Pers_Id` FROM `{$tblPerso}` AS p LEFT JOIN `{$tblIRC}` AS i
                  ON p.`Id_Personnages` = i.`Pers_Id` WHERE p.`Util_Id` != '{$uid}' AND i.`Pers_Id` IS NOT NULL
                  ORDER BY RAND() LIMIT 1";
            if (!$persoOpp = current((array) $db->getRows($q))) {
                $irc->notice($nick, "Désolé, il n'y a actuellement pas d'autres joueurs connectés pour pouvoir "
                    . "effectuer un combat.");
                return false;
            }
        } else {
            //Recherche les informations de personnage spécifié s'il existe, est connecté, n'appartient pas au joueur.
            $q = "SELECT p.*, i.`Pers_Id` FROM `{$tblPerso}` AS p LEFT JOIN `{$tblIRC}` AS i
                  ON p.`Id_Personnages` = i.`Pers_Id` WHERE p.`Nom` = '{$opposant}'";
            if (!$persoOpp = current((array) $db->getRows($q))) {
                $irc->notice($nick, "Désolé, le personnage que vous désirez combattre n'existe pas.");
                return false;
            } elseif (is_null($persoOpp["Pers_Id"])) {
                $irc->notice($nick, "Désolé, {$persoOpp['Nom']} n'est actuellement pas connecté, or vous ne pouvez "
                    . "attaquer un personnage que s'il est connecté.");
                return false;
            } elseif ($persoOpp["Util_Id"] == $uid) {
                $irc->notice($nick, "Désolé, vous ne pouvez pas effectuer de combat un de vos personnages.");
                return false;
            }
        }

        //Prépare les données du combat.
        $nomOpp = $persoOpp['Nom'];
        $pidOpp = $persoOpp["Id_Personnages"];
        $so    = $this->calcSomme($pid);
        $soOpp = $this->calcSomme($pidOpp);
        $rand    = rand(0, $so);
        $randOpp = rand(0, $soOpp);

        //Prépare le message qui sera affiché sur le canal.
        $msg = "{$nom} [{$rand}/{$so}] a provoqué en duel {$nomOpp} [{$randOpp}/{$soOpp}]";

        if ($rand > $randOpp) {
            //Prépare le bonus pour la victoire.
            $mod  = round(($so >= $soOpp ? 0.15 * $soOpp / $so : 0.6 * (1 - $so / $soOpp)) * $next, 0);
            $msg .= " et a gagné ! Cette victoire accélère sa course vers le niveau {$lvl} de "
                  . "{$irpg->convSecondes($mod)}.";

            //Effectue un éventuel coup critique.
            if (rand(1, 35) == 1) {
                $lvlOpp  = $persoOpp["Level"] + 1;
                $modOpp  = round($persoOpp["Next"] * rand(5, 25) / 100, 0);
                $nextOpp = $persoOpp["Next"] + $modOpp;
                $db->req("UPDATE {$tblPerso} SET `Next` = {$nextOpp} WHERE `Nom` = '{$nomOpp}'");
                $irpg->Log($pidOpp, "COUP_CRITIQUE", 0, $modOpp, $nom);

                $msg2 = "{$nom} assène un violent COUP CRITIQUE sur le crâne de {$nomOpp} ! Sa course est ainsi "
                      . "ralentie de {$irpg->convSecondes($modOpp)} vers le niveau {$lvlOpp}. Prochain niveau dans "
                      . "{$irpg->convSecondes($nextOpp)}.";
                $irc->notice($irpg->getNickByUID($persoOpp["Util_Id"]), "{$nom} vient de t'infliger un coup "
                    . "critique ! Ta progression vers le niveau {$lvlOpp} est par conséquent ralentie de "
                    . "{$irpg->convSecondes($modOpp)}. Prochain niveau dans {$irpg->convSecondes($nextOpp)}.");
            }
        } elseif ($rand < $randOpp) {
            //Prépare la pénalité pour la défaite.
            $mod  = round(($so >= $soOpp ? -0.12 * $soOpp / $so : -0.5 * (1 - $so / $soOpp)) * $next, 0);
            $msg .= " et a perdu ! Cette défaite lui inflige une pénalite de {$irpg->convSecondes(-$mod)} avant "
                  . "d'accéder au niveau {$lvl}.";
        } else {
            $mod = 0;
            $msg .= ". Le combat s'est soldé par un match nul.";
        }
        $irpg->Log($pid, "DUEL_MANUEL", 0, -$mod);

        //Met à jour le temps avant le prochain niveau/combat et le nombre de combat.
        $next = max(0, $next - $mod);
        $nbChallenges++;
        $nextChallenge = round(pow(($nbChallenges + 2), 4.3), 0);
        $db->req("UPDATE {$tblPerso} SET `Next` = '{$next}', `ChallengeTimes` = {$nbChallenges},
                  `ChallengeNext` = {$nextChallenge} WHERE `Id_Personnages` = '{$pid}'");

        //Envoi le message du combat sur le canal.
        $msg .= ($mod ? " Prochain niveau dans {$irpg->convSecondes($next)}." : "");
        $irc->privmsg($irc->home, $msg);
        if (!empty($msg2)) {
            $irc->privmsg($irc->home, $msg2);
        }

        //Envoi le message au joueur resumant le combat et le temps d'attente avant un nouveau combat.
        if ($mod) {
            $notice = "{$nom} vient de " . ($mod > 0 ? "gagner " : "perdre ") . $irpg->convSecondes(abs($mod))
                    . " avant d'accéder au niveau {$lvl} en " . ($mod > 0 ? "remportant" : "ratant")
                    . " son combat contre {$nomOpp}.";
        } else {
            $notice = "Le combat s'est terminé par un match nul, {$nom} n'a donc rien gagné cette fois-ci.";
        }
        $irc->notice($nick, $notice . " Vous devez maintenant attendre {$irpg->convSecondes($nextChallenge)} avant "
            . "de pouvoir effectuer un nouveau combat.");
    }

/////////////////////////////////////////////////////////

    function calcSomme($pid)
    {
        // Calcul la somme des objets d'un joueur
        global $db;

        $t = $db->prefix . "Objets";
        $q = "SELECT Level FROM $t WHERE Pers_Id='$pid'";

        if ($db->nbLignes($q) > 0) {
            $res = $db->req($q);
            $somme = 0;
            while ($li = mysql_fetch_array($res)) {
                 $somme = $somme + $li["Level"];
            }
        } else {
            return 0;
        }

        return $somme;
    }

///////////////////////////////////////////////////////////////
}
?>
