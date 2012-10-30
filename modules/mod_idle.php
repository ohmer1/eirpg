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

/**
 * Module mod_idle
 * Calcul de l'idle des joueurs
 * Module indispensable au fonctionnement du jeu.
 *
 * Méthodes inter-modules crées dans ce module:
 * - modIdle_onLvlUp($nick, $uid, $pid, $level2, $next)
 *
 * @author Homer
 * @author cedricpc
 * @created   Samedi   10 Septembre 2005
 * @modified  Mardi    30 Octobre   2012 @ 02:40 (CET)
 */
class idle
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
    var $name;    //Nom du module
    var $version; //Version du module
    var $desc;    //Description du module
    var $depend;  //Modules dont nous sommes dépendants

    //Variables supplémentaires
    var $idleBase; //Niveau de base (lu du fichier de config)
    var $expLvlUp; //Valeur exponentiel de calcul de niveau (lu du fich. de config)
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////

    function loadModule()
    {
        //Constructeur; initialisateur du module
        //S'éxécute lors du (re)chargement du bot ou d'un REHASH
        global $irc, $irpg, $db;

        /* Renseignement des variables importantes */
        $this->name    = "mod_idle";
        $this->version = "1.0.1";
        $this->desc    = "Module calculant l'idle";
        $this->depend  = array("core/0.5.0");

        //Recherche de dépendances
        if (!$irpg->checkDepd($this->depend)) {
            die("$this->name: dépendance non résolue\n");
        }

        //Validation du fichier de configuration spécifique au module
        $cfgKeys    = array("idleBase", "expLvlUp");
        $cfgKeysOpt = array("");

        if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
            die("$this->name: Vérifiez votre fichier de configuration.\n");
        }

        //Initialisation des paramètres du fichier de configuration
        $this->idleBase = $irpg->readConfig($this->name, "idleBase");
        $this->expLvlUp = $irpg->readConfig($this->name, "expLvlUp");
    }

///////////////////////////////////////////////////////////////

    function unloadModule()
    {
        //Destructeur; décharge le module
        //S'éxécute lors du SHUTDOWN du bot ou d'un REHASH
        global $irc, $irpg, $db;

        $irc->deconnexion("SHUTDOWN: mod_idle a été déchargé!");
        $db->deconnexion();
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

        //On retire 15 secondes à tous les personnages en ligne !
        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';
        $db->req('UPDATE `' . $tbPerso . '` SET `Next` = `Next` - 15, `Idled` = `Idled` + 15 WHERE `Id_Personnages`
                  IN (SELECT `Pers_Id` FROM `' . $tbIRC . '` WHERE NOT ISNULL(`Pers_Id`))');

        //On fait passer au niveau suivant les personnages qui doivent l'être.
        $persos = $db->getRows('SELECT `Id_Personnages`, `Util_Id`, `Nom`, `Level`, `Class`, `Next`
                                FROM `' . $tbPerso . '` WHERE `Next` <= 0');
        if (!is_array($persos)) {
            return;
        }

        foreach ($persos as $perso) {
            $this->cmdLvlUp($perso['Id_Personnages'], array($perso));
        }
    }

///////////////////////////////////////////////////////////////

    function cmdLvlUp($pid, $data = false)
    {
        global $irc, $irpg, $db;
        $tbPerso = $db->prefix . 'Personnages';

        if (!is_array($data)) {
            if (!is_numeric($pid)) {
                return false;
            }

            $data = $db->getRows('SELECT `Id_Personnages`, `Util_Id`, `Nom`, `Level`, `Class`, `Next`
                                  FROM `' . $tbPerso . '` WHERE `Id_Personnages` = ' . $pid);
        }

        if (!$data || !count($data)) {
            return false;
        }

        $pid      = $data[0]['Id_Personnages'];
        $uid      = $data[0]['Util_Id'];
        $nomPerso = $data[0]['Nom'];
        $class    = $data[0]['Class'];
        $nick     = $irpg->getNickByUID($uid);
        $level    = $data[0]['Level'] + 1;
        $next     = ($data[0]['Next'] > -15 ? $data[0]['Next'] : 0);

        if ($next > 0) {
            return false;
        }

        //Calcul du nombre de seconde à idler pour atteindre le prochain niveau
        $next += round($this->idleBase * pow($this->expLvlUp, $level), 0);
        $cnext = $irpg->convSecondes($next);

        $db->req('UPDATE `' . $tbPerso . '` SET `Level` = Level + 1, `Next` = ' . $next . '
                  WHERE `Id_Personnages` = ' . $pid);
        $irpg->Log($pid, 'LEVEL_UP', '0', $level - 1, $level);

        $irc->notice($nick, 'Votre personnage ' . $nomPerso . ' vient d\'obtenir le niveau ' . $level . ' ! '
            . 'Prochain niveau dans ' . $cnext . '.');
        $irc->privmsg($irc->home, 'UP! ' . $nomPerso . ', ' . $class . ' vient d\'obtenir le niveau ' . $level
            . ' ! Prochain niveau dans ' . $cnext . '.');

        for ($i = 0; $i != count($irpg->mod); $i++) {
            if (method_exists($irpg->mod[$irpg->modules[$i]], 'modIdle_onLvlUp')) {
                $irpg->mod[$irpg->modules[$i]]->modIdle_onLvlUp($nick, $uid, $pid, $level, $next);
            }
        }
    }

///////////////////////////////////////////////////////////////
}
?>
