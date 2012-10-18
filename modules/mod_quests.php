<?php

/*
 * EpiKnet Idle RPG (EIRPG)
 * Copyright (C) 2005-2012 Francis D (Homer), Womby, cedricpc & EpiKnet
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
 * Module mod_quests
 * @author Womby
 * @author    cedricpc
 * @modified  Thursday 04 November 2010 @ 03:25 (CET)
 */
class quests
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
    var $name;    //Nom du module
    var $version; //Version du module
    var $desc;    //Description du module
    var $depend;  //Modules dont nous sommes dépendants

    //Variables supplémentaires
    var $queteEnCours = -1; //-1:Pas de quete en cours; 1: Quete d'aventure en cours; 2: quete de Royaume en cours
    var $participants = array();
    var $tempsQuete,$probaAllQuete,$probaQueteA;
    var $tempsQueteA,$tempsQueteR;
    var $recompenseA,$recompenseR, $queteSurvivant = false;
    var $MinPenalite,$MaxPenalite,$MinPenaliteAll,$MaxPenaliteAll;
    var $nbrParticipants, $tempsMinIdleA, $tempsMinIdleR, $lvlMinimumA, $lvlMinimumR;
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////

    function loadModule()
    {
        //Constructeur; initialisateur du module
        //S'éxécute lors du (re)chargement du bot ou d'un REHASH
        global $irc, $irpg, $db;

        /* Renseignement des variables importantes */
        $this->name    = "mod_quests";
        $this->version = "0.2.1";
        $this->desc    = "Quetes";
        $this->depend  = array("core/0.5.0");

        //Recherche de dépendances
        if (!$irpg->checkDepd($this->depend)) {
            die("$this->name: dépendance non résolue\n");
        }

        //Validation du fichier de configuration spécifique au module
        $cfgKeys    = array(
            "tempsQueteA", "tempsQueteR", "recompenseA", "recompenseR", "recompenseS", "MinPenalite", "MaxPenalite",
            "MinPenaliteAll", "MaxPenaliteAll", "nbrParticipants", "tempsMinIdleA", "tempsMinIdleR", "tempsMinIdleS",
            "lvlMinimumA", "lvlMinimumR", "lvlMinimumS", "probaAllQuete", "probaQueteA",
        );
        $cfgKeysOpt = array("");

        if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
            die("$this->name: Vérifiez votre fichier de configuration.\n");
        }

        //Initialisation des paramètres du fichier de configuration
        $this->tempsQueteA     = $irpg->readConfig($this->name, "tempsQueteA");
        $this->tempsQueteR     = $irpg->readConfig($this->name, "tempsQueteR");
        $this->recompenseA     = $irpg->readConfig($this->name, "recompenseA");
        $this->recompenseR     = $irpg->readConfig($this->name, "recompenseR");
        $this->recompenseS     = $irpg->readConfig($this->name, "recompenseS");
        $this->MinPenalite     = $irpg->readConfig($this->name, "MinPenalite");
        $this->MaxPenalite     = $irpg->readConfig($this->name, "MaxPenalite");
        $this->MinPenaliteAll  = $irpg->readConfig($this->name, "MinPenaliteAll");
        $this->MaxPenaliteAll  = $irpg->readConfig($this->name, "MaxPenaliteAll");
        $this->nbrParticipants = $irpg->readConfig($this->name, "nbrParticipants");
        $this->tempsMinIdleA   = $irpg->readConfig($this->name, "tempsMinIdleA");
        $this->tempsMinIdleR   = $irpg->readConfig($this->name, "tempsMinIdleR");
        $this->tempsMinIdleS   = $irpg->readConfig($this->name, "tempsMinIdleS");
        $this->lvlMinimumA     = $irpg->readConfig($this->name, "lvlMinimumA");
        $this->lvlMinimumR     = $irpg->readConfig($this->name, "lvlMinimumR");
        $this->lvlMinimumS     = $irpg->readConfig($this->name, "lvlMinimumS");
        $this->probaAllQuete   = $irpg->readConfig($this->name, "probaAllQuete");
        $this->probaQueteA     = $irpg->readConfig($this->name, "probaQueteA");
    }

///////////////////////////////////////////////////////////////

    function unloadModule()
    {
        //Destructeur; décharge le module
        //S'éxécute lors du SHUTDOWN du bot ou d'un REHASH
    }

///////////////////////////////////////////////////////////////

    function onConnect()
    {
    }

///////////////////////////////////////////////////////////////

    function onPrivmsgCanal($nick, $user, $host, $message)
    {
        global $irpg;

        // SI il n'y a aucune quete en cours on ne fait rien du tout (Optimisation).
        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            // On lit le fichier de config pour verifier que l'action est considérée comme pénalité.
            if ($irpg->readConfig('mod_penalites', 'penPrivmsg') != 0) {
                // On verifie que le nick participe a la quete, et si la quete est abandonnée le cas échéant.
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onPrivmsgPrive($nick, $user, $host, $message)
    {
        global $irc, $irpg, $db;

        $message = trim(str_replace("\n", "", $message));
        $message = explode(" ", $message);
        switch (strtoupper($message[0])) {
        case "QUEST":
        case "QUETE":
            $this->cmdQuest($nick);
            break;
        case "QUESTSTART":
            $this->cmdQuestStart($nick);
            break;
        }
    }

///////////////////////////////////////////////////////////////

    function onNoticeCanal($nick, $user, $host, $message)
    {
        global $irpg;

        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            if ($irpg->readConfig('mod_penalites', 'penNotice') != 0) {
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onNoticePrive($nick, $user, $host, $message)
    {
        global $irpg;

        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            if($irpg->readConfig('mod_penalites', 'penNotice') != 0) {
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onJoin($nick, $user, $host, $channel)
    {
    }

///////////////////////////////////////////////////////////////

    function onPart($nick, $user, $host, $channel)
    {
        global $irpg;

        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            if ($irpg->readConfig('mod_penalites', 'penPart') != 0) {
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onNick($nick, $user, $host, $newnick)
    {
        global $irpg;

        if ($this->queteSurvivant || ($this->queteEnCours > 0)) {
            // On verifie que le nick changeant participe a une quete et si c'est le cas
            // on met a jour les informations concernant ce participant dans le tableau des participants à la quete.
            foreach ($this->participants as $queteId => $persos) {
                foreach ($persos as $i => $perso) {
                    if ($persos[1] == $nick) {
                        $this->participants[$queteId][$i][1] = $newnick;
                    }
                }
            }

            if (!$irpg->pause && ($irpg->readConfig('mod_penalites', 'penNick') != 0)) {
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onKick($nick, $user, $host, $channel, $nickkicked)
    {
        global $irpg;

        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            if ($irpg->readConfig('mod_penalites', 'penKick') != 0) {
                $this->verifFinQuete($nickkicked);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onCTCP($nick, $user, $host, $ctcp)
    {
    }

///////////////////////////////////////////////////////////////

    function onQuit($nick, $user, $host, $reason)
    {
        global $irpg;

        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            if ($irpg->readConfig('mod_penalites', 'penQuit') != 0) {
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function on5Secondes()
    {
    }

///////////////////////////////////////////////////////////////

    function on10Secondes()
    {
    }

///////////////////////////////////////////////////////////////

    function on15Secondes()
    {
        global $irc, $irpg, $db;

        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';

        // Si il n'y a aucune quete en cours, on a une chance sur 500 d'un mettre une en route.
        if (!$irpg->pause) {
            // Si une quete est mise en route il y a 80 % de chance que ce soit une quete d'aventure,
            // 20 % une quete de Royaume s'il n'y a pas de quête du survivant, 10 % chacune dans le cas contraire.
            // Sinon, s'il y a une quete en cours, on verifie le temps de quete restant.
            if (($this->queteEnCours < 1) && !$this->queteSurvivant && (rand(1, $this->probaAllQuete) == 1)) {
                $proba = rand(1, 100);
                if (($this->nbrParticipants > 1) && ($proba > 50 + round($this->probaQueteA / 2))) {
                    $this->queteSurvivant = $this->queteSurvivant();
                } else {
                    $quete = ($proba > $this->probaQueteA ? $this->queteRoyaume() : $this->queteAventure());
                    $this->queteEnCours = $quete;
                }
            } elseif (($this->queteEnCours == 1) || ($this->queteEnCours == 2)) {
                // S'il reste encore suffisamment du temps, on enlève 15 secondes.
                // Sinon on donne une recompense à tout les personnages qui ont participé jusqu'au bout.
                if ($this->tempsQuete > 15) {
                    $this->tempsQuete -= 15;
                } elseif (!empty($this->participants[$this->queteEnCours])) {
                    $pourcent = ($this->queteEnCours == 1 ? $this->recompenseA : $this->recompenseR);
                    $recompense = 1 - $pourcent / 100;

                    foreach ($this->participants[$this->queteEnCours] as $perso) {
                        if ($perso[0] != -1) {
                            $pid   = $perso[0];
                            $db->req("UPDATE $tbPerso SET Next = Next * $recompense WHERE Id_Personnages = '$pid'");
                        }
                    }

                    $participants = $this->listerParticipants($this->participants[$this->queteEnCours]);
                    if ($this->queteEnCours == 1) {
                        $message = "{$participants[1]} {e}s(on)t revenu(s) de {sa}(leur) quête et {a}(ont) rempli "
                                 . "l'objectif ! Bravo, voici {ta}(votre) récompense : $pourcent % de {ton}(vos) "
                                 . "TTL sont enlevés !";
                    } else {
                        $message = "{$participants[1]} {e}s(on)t revenu(s) de {sa}(leur) quête à temps. Le royaume "
                                 . "est sauvé... Il(s) ser{a}(ont) largement récompensé(s) : $pourcent % de "
                                 . "(leur)s{on} TTL sont enlevés !";
                    }
                    $irc->privmsg($irc->home, $this->accorder($message, $participants[0]));

                    $this->queteEnCours = -1;
                    unset($this->participants[$this->queteEnCours]);
                } else {
                    $this->queteEnCours = -1;
                }
            }
        }
    }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

    function queteAventure()
    {
        global $irpg, $irc, $db;

        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';
        $tbTxt   = $db->prefix . 'Textes';

        // La query suivante va retourner le nombre de personnage voulu au hasard dont le level est suffisant et dont
        // le temps d'idle est suffisant. Le group by sur Util_id permet de ne recuperer qu'un personnage par user.
        $query = "SELECT P.Id_Personnages, I.Nick, P.Nom, P.Util_id, P.Level FROM $tbIRC AS I
                  JOIN (SELECT * FROM $tbPerso ORDER BY RAND()) AS P ON P.Id_Personnages = I.Pers_id
                  WHERE (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(P.LastLogin)) > {$this->tempsMinIdleA}
                  AND P.Level >= {$this->lvlMinimumA} GROUP BY Util_id ORDER BY RAND() LIMIT {$this->nbrParticipants}";
        $participants = $db->getRows($query);

        // Si le nombre de personnage retourné est inferieur au nombre voulut on ne peut pas commencé la quête
        // Sinon, on stock les infos retournées par la requête.
        if (!is_array($participants) || (count($participants) != $this->nbrParticipants)) {
            return -1;
        }
        $this->participants[1] = $participants;

        // On prend un texte de quete au hasard.
        $message = reset(reset($db->getRows("SELECT Valeur FROM $tbTxt WHERE Type = 'Qa' ORDER BY RAND() LIMIT 1")));

        // le temps de quete est etablit selon le temps de quete desiré plus un temps au hasard entre 1
        // et ce temps de quete desiré. On peut donc passer du simple au double au hasard.
        $this->tempsQuete = $this->tempsQueteA + rand(1, $this->tempsQueteA);
        $temps = $irpg->convSecondes($this->tempsQuete);

        //On établi la liste des participants dans une chaine.
        $participants = $this->listerParticipants($participants);
        $irc->privmsg($irc->home, $this->accorder("{$participants[1]} {a}(ont) été choisi(s) pour $message Il(s) "
            . "{a}(ont) $temps pour en revenir...", $participants[0]));

        return 1;
    }

///////////////////////////////////////////////////////////////

    function queteRoyaume()
    {
        global $irpg, $irc, $db;

        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';
        $tbTxt   = $db->prefix . 'Textes';

        // La query suivante va retourner le nombre de personnage voulu au hasard dont le level est suffisant
        // et dont le temps d'idle est suffisant
        $query = "SELECT P.Id_Personnages, I.Nick, P.Nom, P.Util_id, P.Level FROM $tbIRC AS I
                  JOIN (SELECT * FROM $tbPerso ORDER BY RAND()) AS P ON P.Id_Personnages = I.Pers_id
                  WHERE (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(P.LastLogin)) > {$this->tempsMinIdleR}
                  AND P.Level >= {$this->lvlMinimumR} GROUP BY Util_id ORDER BY RAND() LIMIT {$this->nbrParticipants}";
        $participants = $db->getRows($query);

        // Si le nombre de personnage retourné est inferieur au nombre voulut on ne peut pas commencé la quête
        // Sinon, on stock les infos retournées par la requête.
        if (!is_array($participants) || (count($participants) != $this->nbrParticipants)) {
            return -1;
        }
        $this->participants[2] = $participants;

        // On prend un texte de quete au hasard.
        $message = reset(reset($db->getRows("SELECT Valeur FROM $tbTxt WHERE Type = 'Qr' ORDER BY RAND() LIMIT 1")));

        // le temps de quete est etablit selon le temps de quete desiré plus un temps au hasard entre 1 et ce temps
        // de quete desiré. On peut donc passer du simple au double au hasard.
        $this->tempsQuete = $this->tempsQueteR + rand(1, $this->tempsQueteR);
        $temps = $irpg->convSecondes($this->tempsQuete);

        //On établi la liste des participants dans une chaine.
        $participants = $this->listerParticipants($participants);
        $irc->privmsg($irc->home, $this->accorder("Quête de Royaume ! {$participants[1]} {a}(ont) été choisi(s) "
            . "pour $message Il(s) {a}(ont) $temps pour en revenir...", $participants[0]));

        return 2;
    }

///////////////////////////////////////////////////////////////

    function queteSurvivant()
    {
        global $irpg, $irc, $db;

        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';
        $tbTxt   = $db->prefix . 'Textes';

        // La query suivante va retourner le nombre de personnage voulu au hasard dont le level est suffisant
        // et dont le temps d'idle est suffisant
        $query = "SELECT P.Id_Personnages, I.Nick, P.Nom, P.Util_id, P.Level FROM $tbIRC AS I
                  JOIN (SELECT * FROM $tbPerso ORDER BY RAND()) AS P ON P.Id_Personnages = I.Pers_id
                  WHERE (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(P.LastLogin)) > {$this->tempsMinIdleR}
                  AND P.Level >= {$this->lvlMinimumR} GROUP BY Util_id ORDER BY RAND() LIMIT {$this->nbrParticipants}";
        $participants = $db->getRows($query);

        // Si le nombre de personnage retourné est inferieur au nombre voulut on ne peut pas commencé la quête
        // Sinon, on stock les infos retournées par la requête.
        if (!is_array($participants) || (count($participants) != $this->nbrParticipants)) {
            return -1;
        }
        $this->participants[0] = $participants;

        // On prend un texte de quete au hasard.
        $message = reset(reset($db->getRows("SELECT Valeur FROM $tbTxt WHERE Type = 'Qs' ORDER BY RAND() LIMIT 1")));

        //On établi la liste des participants dans une chaine.
        $participants = next($this->listerParticipants($participants));
        $irc->privmsg($irc->home, "Quête du Survivant ! $participants ont été choisis pour $message Le dernier à en "
            . 'revenir sera déclaré vainqueur...');

        return true;
    }

//////////////////////////////////////////////////////

    function cmdQuestStart($nick)
    {
        global $irpg, $irc, $db;

        if (!$irpg->pause) {
            $uid = $irpg->getUsernameByNick($nick, true);
            if ($irpg->getAdminlvl($uid[1]) >= 5) {
                $proba = rand(1, 100);
                if (!$this->queteSurvivant && ($this->nbrParticipants > 1)
                    && (($this->queteEnCours > 0) || ($proba > 50 + round($this->probaQueteA / 2)))
                ) {
                    $this->queteSurvivant = $this->queteSurvivant();
                    if (!$this->queteSurvivant) {
                        $irc->notice($nick, "Désolé, il n'y a pas assez de joueurs pour entâmer une quête du "
                            . 'survivant !');
                    }
                } elseif ($this->queteEnCours < 1) {
                    $quete = ($proba > $this->probaQueteA ? $this->queteRoyaume() : $this->queteAventure());
                    $this->queteEnCours = $quete;
                    if ($quete < 1) {
                        $irc->notice($nick, "Désolé, il n'y a pas assez de joueurs pour débuter une quête "
                            . ($proba > $this->probaQueteA ? 'du Royaume !' : "d'Aventure !"));
                    }
                } else {
                    $irc->notice($nick, $this->accorder('Il y a déjà {une}(des) quête(s) en cours !',
                        count($this->participants)));
                    $this->cmdQuest($nick);
                }
            } else {
                $irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
            }
        } else {
            $irc->notice($nick, "Le jeu est en pause, aucune information n'est disponible.");
        }
    }

////////////////////////////////////////////////////

    function cmdQuest($nick)
    {
        global $irpg, $irc;

        if (!$irpg->pause) {
            if ($this->queteSurvivant) {
                //On établi la liste des participants dans une chaine.
                $participants = $this->listerParticipants($this->participants[0]);
                $irc->notice($nick, 'Il y a une quête du survivant en cours ! Les participants encore en lice sont '
                    . ": {$participants[1]}.");
            }

            if ($this->tempsQuete && (($this->queteEnCours == 1) || ($this->queteEnCours == 2))) {
                $temps = $irpg->convSecondes($this->tempsQuete);

                //On établi la liste des participants dans une chaine.
                $participants = $this->listerParticipants($this->participants[$this->queteEnCours]);

                $irc->notice($nick, 'La quête d' . ($this->queteEnCours == 1 ? "'Aventure" : 'e Royaume')
                    . " en cours prendra fin dans {$temps}. Participant" . ($participants[0] > 1 ? 's' : '')
                    . " : {$participants[1]}.");
            } elseif (!$this->queteSurvivant) {
                $irc->notice($nick, "Aucune quête n'est en cours actuellement.");
            }
        } else {
            $irc->notice($nick, "Le jeu est en pause, aucune information n'est disponible.");
        }
    }

///////////////////////////////////////////////////////////////

    function verifFinQuete($nick)
    {
        global $irc, $irpg, $db;

        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';

        // On boucle sur chaque participant des quetes et on arrete si on a trouvé que le nick participe a la quete.
        foreach ($this->participants as $queteId => $persos) {
            foreach ($persos as $i => $perso) {
                // Si le nick est participant a la quete et qu'il n'a pas encore abandonné la quete on lui inflige
                // une penalité et on l'annonce sur le canal, on l'inscrit dans les logs.
                if (($perso[1] == $nick) && ($perso[0] != -1)) {
                    $pid      = $perso[0];
                    $penalite = 1 + rand($this->MinPenalite, $this->MaxPenalite) / 100;
                    $db->req("UPDATE $tbPerso SET Next = Next * $penalite WHERE Id_Personnages = '$pid'");

                    $this->participants[$queteId][$i][0] = -1;
                    $participants = $this->listerParticipants($this->participants[$queteId]);

                    $irpg->Log($pid, 'QUETE_ABANDONNÉE', $penalite, $queteId);
                    $irc->privmsg($irc->home, $this->accorder($irpg->getNomPersoByPID($pid)
                        . ($queteId > 0 ? ' rebrousse chemin dans cette quête ardue... Taxé par (se)s{on} '
                        . 'compatriote(s) de couardise, le voilà blamé !' : ' abandonne lâchement sa lutte contre '
                        . '(se)s{on} valeureux adversaire(s)... Il a donc été châtié par ce(s) dernier(s) !'),
                        $participants[0]));

                    //S'il n'y a plus de participant à la quête après l'abandon du joueur, on considère que la quête
                    // est abandonnée.
                    if ($participants[0] < 1) {
                        if ($queteId == 1) {
                            $irc->privmsg($irc->home, 'La quête a échouée... Les aventuriers sont tous revenus '
                                . 'bredouilles...');
                        } elseif ($queteId == 2) {
                            $penalite = 1 + rand($this->MinPenaliteAll, $this->MaxPenaliteAll) / 100;
                            $db->req("UPDATE $tbPerso AS P, $tbIRC AS I SET P.Next = P.Next * $penalite
                                      WHERE P.Id_Personnages = I.Pers_Id");

                            $penalises = (array) $db->getRows("SELECT P.Id_Personnages FROM $tbPerso AS P, $tbIRC AS I
                                                               WHERE P.Id_Personnages = I.Pers_Id");
                            foreach ($penalises as $penalise) {
                                $irpg->Log($penalise[0], 'QUETE_ROYAUME_ECHOUÉE', $penalite, 2);
                            }

                            $irc->privmsg($irc->home, 'La quête a échouée... Le royaume est menacé et chaque '
                                . 'habitant en subira les conséquences...');
                        }
                        $this->queteEnCours = -1;
                        unset($this->participants[$queteId]);
                    } elseif (($queteId == 0) && ($participants[0] == 1)) {
                        $pid = intval($participants[2][0][0]);
                        $perso = $irpg->getNomPersoByPID($pid);
                        $recompense = 1 - $this->recompenseS / 100;
                        $db->req("UPDATE $tbPerso SET Next = Next * $recompense WHERE Id_Personnages = $pid");

                        $irc->privmsg($irc->home, "Nous avons un gagnant dans cette quête du Survivant ! $perso "
                            . 'sera largement récompensé pour sa bravoure !');

                        $this->queteSurvivant = false;
                        unset($this->participants[$queteId]);
                    }

                    $queteAbandonnee = true;
                    break;
                }
            }
        }
        return !empty($queteAbandonnee);
    }

///////////////////////////////////////////////////////////////

    function listerParticipants($participants) {
        global $irpg;

        if (!is_array($participants)) {
            return false;
        }

        $listeParticipants = array();
        foreach ($participants as $perso) {
            if (empty($perso[0]) || ($perso[0] == -1)) {
                continue;
            }
            $listeParticipants[] = array($perso, $irpg->getNomPersoByPID($perso[0]));
        }

        if (empty($listeParticipants)) {
            return array(0, '', array());
        }

        $participants = array_map('end', $listeParticipants);
        return array(
            count($listeParticipants),
            implode(' et ', array(implode(', ', array_slice($participants, 0, -1)), end($participants))),
            array_map('reset', $listeParticipants)
        );
    }

///////////////////////////////////////////////////////////////

    function accorder($texte, $nombre) {
        if ($nombre < 2) {
            $texte = preg_replace('#(?<!\\\)\(.*?[^\\\]?\)#', '', $texte);
        } else {
            $texte = preg_replace('#(?<!\\\)\{.*?[^\\\]?\}#', '', $texte);
        }
        return preg_replace('#(?<!\\\)([)(}{])|\\\(?=[)(}{])#', '', $texte);
    }

///////////////////////////////////////////////////////////////
}
?>
