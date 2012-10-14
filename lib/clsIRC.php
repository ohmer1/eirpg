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
 * Classe IRC; g�re tout le c�t� IRC du bot
 *
 * @author Homer
 * @author cedricpc
 * @created 30 mai 2005
 * @modified 19 Avril 2010
 */
class IRC
{
    ///////////////////////////////////////////////////////////////
    // Variables priv�es
    ///////////////////////////////////////////////////////////////
    var $sirc;     //Socket vers le serveur IRC
    var $debug;    //Flag pour les informations de d�buguage
    var $users;    //Utilisateurs connect�s sur les canaux
    var $me;       //Pseudo du bot sur IRC
    var $home;     //Canal principal du bot
    var $ready;    //Pr�t � d�marrer !
    var $lastData; //Timestamp UNIX du moment o� une donn�e a �t� re�u pour la dern fois
    var $exit;     //Coupe la connexion IRC et coupe le bot !
    ///////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////////////
    // M�thodes priv�es
    // Mais PHP ne semble pas faire la diff�rence entre
    // priv� et publique :)
    ///////////////////////////////////////////////////////////////

    /* M�thodes �v�nementielles */

    function onPing($data)
    {
        $idping = split(":", $data);
        $this->sendRaw("PONG $idping[1]");
    }

///////////////////////////////////////////////////////////////

    function onMOTD($data, $me)
    {
        global $irpg;

        $channel = strtoupper($irpg->readConfig("IRC","channel"));
        $key     = $irpg->readConfig("IRC", "key");
        $modes   = $irpg->readConfig("IRC", "modes");
        $nspass  = $irpg->readConfig("IRC", "nspass");

        //Qui suis-je?
        $this->me = $me;

        //O� suis-je?
        $this->home = $channel;

        //Applications des modes
        $this->sendRaw("UMODE2 $modes");

        //Identification � NS et on patiente 1 secondes
        if (!empty($nspass)) {
            $this->sendRaw("NICKSERV IDENTIFY $nspass");
            sleep(1);
        }

        //On joint ensuite le canal principal
        $this->join($channel, $key);

        //Appel aux modules
        $i = 0;
        while ($i != count($irpg->mod)) {
            if (method_exists($irpg->mod[$irpg->modules[$i]], "onConnect")) {
                $irpg->mod[$irpg->modules[$i]]->onConnect();
            }
            $i++;
        }

        //On est maintenant pr�t !
        $this->ready = true;
    }

///////////////////////////////////////////////////////////////

    function onPrivmsgCanal($nick, $user, $host, $message)
    {
        global $irpg;

        if (!$irpg->pause) {
            //Appel aux modules
            $i = 0;
            while ($i != count($irpg->mod)) {
                if (method_exists($irpg->mod[$irpg->modules[$i]], "onPrivmsgCanal")) {
                    $irpg->mod[$irpg->modules[$i]]->onPrivmsgCanal(addslashes($nick), $user, $host, $message);
                }
                $i++;
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onPrivmsgPrive($nick, $user, $host, $message)
    {
        global $irpg;

        if (!$irpg->pause) {
            //Appel aux modules
            $i = 0;
            while ($i != count($irpg->mod)) {
                if (method_exists($irpg->mod[$irpg->modules[$i]], "onPrivmsgPrive")) {
                    $irpg->mod[$irpg->modules[$i]]->onPrivmsgPrive($nick, $user, $host, $message);
                }
                $i++;
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onNoticeCanal($nick, $user, $host, $message)
    {
        global $irpg;

        if (!$irpg->pause) {
            //Appel aux modules
            $i = 0;
            while ($i != count($irpg->mod)) {
                if (method_exists($irpg->mod[$irpg->modules[$i]], "onNoticeCanal")) {
                    $irpg->mod[$irpg->modules[$i]]->onNoticeCanal($nick, $user, $host, $message);
                }
                $i++;
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onNoticePrive($nick, $user, $host, $message)
    {
        global $irpg;

        if (!$irpg->pause) {
            //Appel aux modules
            $i = 0;
            while ($i != count($irpg->mod)) {
                if (method_exists($irpg->mod[$irpg->modules[$i]], "onNoticePrive")) {
                    $irpg->mod[$irpg->modules[$i]]->onNoticePrive($nick, $user, $host, $message);
                }
                $i++;
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onJoin($nick, $user, $host, $channel)
    {
        global $irpg;

        if (!$irpg->pause) {
            //Appel aux modules
            $i = 0;
            while ($i != count($irpg->mod)) {
                if (method_exists($irpg->mod[$irpg->modules[$i]], "onJoin")) {
                    $irpg->mod[$irpg->modules[$i]]->onJoin($nick, $user, $host, $channel);
                }
                $i++;
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onPart($nick, $user, $host, $channel)
    {
        global $irpg;

        if (!$irpg->pause) {
            //Appel aux modules
            $i = 0;
            while ($i != count($irpg->mod)) {
                if (method_exists($irpg->mod[$irpg->modules[$i]], "onPart")) {
                    $irpg->mod[$irpg->modules[$i]]->onPart($nick, $user, $host, $channel);
                }
                $i++;
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onNick($nick, $user, $host, $newnick)
    {
        global $irpg;

        if (!$irpg->pause) {
            //Appel aux modules
            $i = 0;
            while ($i != count($irpg->mod)) {
                if (method_exists($irpg->mod[$irpg->modules[$i]], "onNick")) {
                    $irpg->mod[$irpg->modules[$i]]->onNick($nick, $user, $host, $newnick);
                }
                $i++;
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onKick($nick, $user, $host, $channel, $nickkicked)
    {
        global $irpg;

        if (!$irpg->pause) {
            //Appel aux modules
            $i = 0;
            while ($i != count($irpg->mod)) {
                if (method_exists($irpg->mod[$irpg->modules[$i]], "onKick")) {
                    $irpg->mod[$irpg->modules[$i]]->onKick($nick, $user, $host, $channel, $nickkicked);
                }
                $i++;
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onCTCP($nick, $user, $host, $ctcp)
    {
        global $irpg;

        //R�ponse au CTCP VERSION
        if ($ctcp == "VERSION") {
            /* Ne pas modifier ici, sinon BOOM! :) */
            $version = $irpg->readConfig("IRPG", "version");
            $this->sendRaw("NOTICE $nick :\001VERSION EIRPG v$version; http://www.eirpg.com\001");

            //Liste des modules charg�s
            $i = 0;
            while ($i != count($irpg->mod)) {
                if (empty($modules)) {
                    $modules = $irpg->modules[$i] . "/" . $irpg->mod[$irpg->modules[$i]]->version;
                } else {
                    $modules = $modules . ", " . $irpg->modules[$i] . "/" . $irpg->mod[$irpg->modules[$i]]->version;
                }

                $i++;
            }
            $this->sendRaw("NOTICE $nick :\001VERSION Modules charg�s: $modules\001");
        }

        //Appel aux modules
        $i = 0;
        while ($i != count($irpg->mod)) {
            if (method_exists($irpg->mod[$irpg->modules[$i]], "onCTCP")) {
                $irpg->mod[$irpg->modules[$i]]->onCTCP($nick, $user, $host, $ctcp);
            }
            $i++;
        }
    }

///////////////////////////////////////////////////////////////

    function onQuit($nick, $user, $host, $reason)
    {
        global $irpg;

        if (!$irpg->pause) {
            //Appel aux modules
            $i = 0;
            while ($i != count($irpg->mod)) {
                if (method_exists($irpg->mod[$irpg->modules[$i]], "onQuit")) {
                    $irpg->mod[$irpg->modules[$i]]->onQuit($nick, $user, $host, $reason);
                }
                $i++;
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onNames($channel, $names)
    {
        global $db;

        $table = $db->prefix . "IRC";

        /*
            * DEPLACE -- voir onWHO()
        $names = split(" ", $names);
        $i = 0;
        while ($i != count($names)) {
            $names[$i] = ltrim($names[$i], "@");
            $names[$i] = ltrim($names[$i], "%");
            $names[$i] = ltrim($names[$i], "+");
            $names[$i] = trim($names[$i]);

            if ((!empty($names[$i])) && ($names[$i] != $this->me)) {
                $db->req("INSERT INTO $table (`Nick`, `Channel`) VALUES ('$names[$i]', '#$channel')");
            }

            $i++;
        }
        */

        //On envoit un /WHO pour r�cup�rer les user@hosts de
        //tous les utilisateurs et permettre l'auto-login
        $this->sendRaw("WHO #$channel");
    }

///////////////////////////////////////////////////////////////

    function onWho($nick, $user, $host, $server, $channel)
    {
        global $db, $irpg;

        $tbIRC = $db->prefix . "IRC";
        $channel = strtoupper($channel);

        if ($nick != $this->me) {
            $db->req("INSERT INTO $tbIRC (`Nick`, `Channel`, `UserHost`) VALUES ('$nick', '$channel', '$user@$host')");
        }

        //Gestion auto-login
        if (($channel == $this->home) && ($nick != $this->me)) {
            $tbPerso = $db->prefix . "Personnages";
            $tbUtil  = $db->prefix . "Utilisateurs";

            $query = "SELECT Pers_Id FROM $tbIRC WHERE Nick='$nick' And UserHost='$user@$host'
                      And NOT ISNULL(Pers_Id) And Channel='$channel'";
            $nb = $db->nbLignes($query);

            if ($nb >= 1) {
                //L'utilisateur peut donc �tre relogu� automatiquement
                $username = $db->getRows("SELECT Username FROM $tbUtil WHERE Id_Utilisateurs = (
                    SELECT Util_Id FROM $tbPerso WHERE Id_Personnages = ($query LIMIT 0,1)
                )");
                $username = $username[0]["Username"];

                $persodb = $db->getRows($query);
                $i = 0;
                while ($i != count($persodb)) {
                    $irpg->mod["core"]->autologged[] = array($username, $persodb[$i]["Pers_Id"], $nick, "$user@$host");
                    $i++;
                }

                $irpg->mod["core"]->users["$username"] = $nick;
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onEndWho($channel)
    {
        global $irc, $db, $irpg, $nbExecute;

        $channel = strtoupper($channel);
        if ($channel == $this->home) {

            if (empty($nbExecute)) {
                $nbExecute = 0;
            }

            if ($nbExecute == 0) {
                //On vire tous les persos identifi�s de la table IRC
                $table = $db->prefix . "IRC";
                $db->req("DELETE FROM $table WHERE Not ISNULL(Pers_Id)");
            }

            //Maintenant, on r�insert les persos autorelogu�s
            //via les r�sultats du /who
            $i = 0;
            $lstAuto = array();
            while ($i != count($irpg->mod["core"]->autologged)) {
                $auto = $irpg->mod["core"]->autologged[$i];

                if ($auto[1] != "") {
                    $db->req("INSERT INTO $table (`Pers_Id`, `Nick`, `UserHost`, `Channel`) VALUES (
                        '$auto[1]', '$auto[2]', '$auto[3]', '$this->home'
                    )");

                    $perso = $irpg->getNomPersoByPID($auto[1]);

                    $lstAuto[] = $perso;
                }
                $i++;
            }
            $lstAuto = implode($lstAuto, ', ');

            if (($i == 1) && (!empty($lstAuto))) {
                $this->privmsg($this->home, "Le personnage suivant a �t� automatiquement relogu� : $lstAuto.");
            } elseif (($i > 1) && (!empty($lstAuto))) {
                $this->privmsg($this->home, "Les personnages suivants ont �t� automatiquement relogu�s : $lstAuto.");
            }

            $irpg->mod["core"]->resetAutoLogin();

            $nbExecute++;
        }
    }

///////////////////////////////////////////////////////////////

    /* Autres m�thodes */
    function Timer5Sec(&$dix, &$quinze)
    {
        global $irpg, $db, $last5sec;

        if ((($last5sec + 5) <= time()) && ($this->ready)) {
            if (!$irpg->pause) {
                //Appel aux modules
                $i = 0;
                while ($i != count($irpg->mod)) {
                    if (method_exists($irpg->mod[$irpg->modules[$i]], "on5Secondes")) {
                        $irpg->mod[$irpg->modules[$i]]->on5Secondes();
                    }
                    $i++;
                }
            }

            $last5sec = time();
            $dix++;
            $quinze++;

            if ($dix == 2) {
                if (!$irpg->pause) {
                    //Appel aux modules
                    $i = 0;
                    while ($i != count($irpg->mod)) {
                        if (method_exists($irpg->mod[$irpg->modules[$i]], "on10Secondes")) {
                            $irpg->mod[$irpg->modules[$i]]->on10Secondes();
                        }
                        $i++;
                    }
                }
                $dix = 0;
            }

            if ($quinze == 3) {
                if (!$irpg->pause) {
                    //Appel aux modules
                    $i = 0;
                    while ($i != count($irpg->mod)) {
                        if (method_exists($irpg->mod[$irpg->modules[$i]], "on15Secondes")) {
                            $irpg->mod[$irpg->modules[$i]]->on15Secondes();
                        }
                        $i++;
                    }
                }
                $quinze = 0;

                //Si la connexion DB est perdu; on retente une
                //nouvelle connexion toutes les 15 secondes..
                if ((!$db->connected) && (!$this->exit)) {
                    if ($db->connexion($irpg->readConfig("SQL", "host"), $irpg->readConfig("SQL", "login"),
                        $irpg->readConfig("SQL", "password"), $irpg->readConfig("SQL", "base"),
                        $irpg->readConfig("SQL", "prefix")
                    )) {
                        //On r�initialise notre table IRC et objets, comme si le bot venait
                        //d'�tre red�marr�
                        $irpg->mod["core"]->users = array();
                        $irpg->mod["core"]->autologged = array();

                        $table = $db->prefix . "IRC";
                        $channel = $irpg->readConfig("IRC", "channel");
                        $db->req("DELETE FROM $table WHERE ISNULL(Pers_Id)");

                        //On envoit un NAMES pour s'assurer d'avoir
                        //une bd � jour..
                        $this->sendRaw("NAMES $this->home"); //TODO: G�rer le multi-chans

                        $this->privmsg($this->home, "La connexion au serveur de bases de donn�es a �t� r�tablie. "
                            . "Le jeu est de nouveau actif. Bon idle !");
                        $irpg->pause = false; //C'est reparti !
                    }
                }
            }
        }
    }

///////////////////////////////////////////////////////////////

    function checkTimeout()
    {
        if ($this->lastData + 180 < mktime()) {
            $this->deconnexion("Perte de connexion vers le serveur IRC... Reconnexion.");
            return true;
        } else {
            return false;
        }
    }

///////////////////////////////////////////////////////////////

    function updateTimeout()
    {
        $this->lastData = mktime();
    }

    ///////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////
    // M�thodes publiques
    ///////////////////////////////////////////////////////////////

    /**
     * Envoi des donn�es au serveur IRC
     *
     * @author Homer
     * @created 30 mai 2005
     * @modified 19 juin 2005
     * @param data     - Donn�es � envoyer
     * @return boolean - true si l'envoi a r�ussi, false sinon.
     */
    function sendRaw($data)
    {
        global $irpg;

        if ($this->debug) {
            $irpg->alog("<-- $data");
        }

        //$charset = $irpg->readConfig("IRC", "charset");
        //$ok = socket_write($this->sirc, iconv("ISO-8859-15", $charset, $data . "\n"));
        $ok = socket_write($this->sirc, $data . "\n");
        if ($ok) {
            return true;
        } else {
            return false;
        }
    }

///////////////////////////////////////////////////////////////

    /**
     * JOIN : join un canal IRC
     *
     * @author Homer
     * @created 30 mai 2005
     * @modified 30 mai 2005
     * @param channel  - Canal � joindre
     * @param key      - Cl� du canal � joindre (facultatif)
     * @return boolean - true si la d�connexion r�ussie, false sinon.
     */
    function join($channel, $key = "")
    {
        $ok = $this->sendRaw("JOIN $channel $key");
        if ($ok) {
            return true;
        } else {
            return false;
        }
    }


///////////////////////////////////////////////////////////////

    /**
     * Constructeur; connexion � un serveur IRC
     *
     * @author Homer
     * @created 30 mai 2005
     * @modified 1er juin 2005
     * @param server   - Adresse du serveur IRC
     * @param port     - Port de connexion au serveur
     * @param realname - Realname du bot
     * @param nick     - Pseudo du bot
     * @param pass     - Mot de passe de connexion au serveur (facultatif)
     * @param debug    - Flag debug
     * @return boolean - true si connexion r�ussie, false sinon.
     */
    function connexion($server, $port, $user, $realname, $nick, $bind, $pass = "", $debug = false)
    {
        global $irpg;

        $this->debug    = $debug;
        $this->lastData = mktime();
        $this->exit     = false;

        $this->sirc = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)
            || die ("Impossible de cr�er le socket IRC\n");

        if ($bind != "") {
            socket_bind($this->sirc, $bind);
        }

        socket_connect($this->sirc, $server, $port)
            || die ("Impossible de se connecter au serveur IRC\n");

        if ($this->sirc) {
            $irpg->alog("Connexion au serveur IRC...", true);
            if ($pass != "") {
                $this->sendRaw("PASS $pass"); //Mot de passe d'acc�s au serveur
            }
            $this->sendRaw("NICK $nick");
            $ok = $this->sendRaw("USER $user localhost $server :$realname");

            return true;
        } else {
            return false;
        }
    }

///////////////////////////////////////////////////////////////

    /**
     * M�thode d'�coute du socket IRC
     * Boucle tant que le socket est ouvert
     *
     * @author Homer
     * @author cedricpc
     * @created 20 juin 2005
     * @modified 19 Avril 2010
     * @param none
     * @return none
     */
    function boucle()
    {
        global $irpg;

        $irpg->alog("D�marr� avec succ�s.", true);
        $last5sec = time();
        $dix      = 0;
        $quinze   = 0;
        socket_set_nonblock($this->sirc);
        $charset = $irpg->readConfig("IRC", "charset");

        // lecture des ignores
        $ignoresN = $irpg->getIgnoresN();
        $ignoresH = $irpg->getIgnoresH();

        $buffer = '';
        while (true) {
            if ($this->exit) {
                break;
            }

            $buf = @socket_read($this->sirc, 4096);
            //l'encodage interne du bot est en ISO-8859-15, il faut donc convertir ce qui vient d'IRC en ISO
            //$buf = iconv($charset, "ISO-8859-15", $buf);

            if (empty($buf)) {
                $this->Timer5Sec($dix, $quinze);
                if ($this->checkTimeout()) {
                    break;
                }
                sleep(1); //Sans sleep, on bouffe 100% du CPU..
                continue;
            }

            $this->updateTimeout();

            if (!strpos($buf, "\n")) { //Si ne contient aucun retour, on bufferise
                $buffer = $buffer . $buf;
                $data   = ""; //rien � envoyer
            } else {
                //Si contient au moins un retour,
                //on v�rifie que le dernier caract�re en est un
                if (substr($buf, -1, 1) == "\n") {
                    //alors on additionne ces donn�es au buffer
                    $data   = $buffer . $buf;
                    $buffer = ""; //on vide le buffer
                } else {
                    //si le dernier caract�re n'est pas un retour � la
                    //ligne, alors on envoit tout jusqu'au dernier retour
                    //puis on bufferise le reste
                    $buffer = $buffer . substr($buf, strrchr($buf, "\n"));
                    $data   = substr($buf, 0, strrchr($buf, "\n"));
                    $data   = $buffer . $data;
                    $buffer = ""; //on vide le buffer
                }
            }

            $data = split("\n", $data);

            for ($i = 0; $i < count($data); $i++) {
                if ($this->debug) {
                    $irpg->alog("--> $data[$i]");
                }

                //On �charpe notre $data[$i] pour le traitement par regexp
                $dataregexp = addslashes($data[$i]);
                //$dataregexp = preg_quote($data[$i]);

                //Ping! Pong!
                if (ereg("^PING ", $dataregexp)) {
                    global $nbExecute;
                    unset($nbExecute); //Reset de la variable qui ne sert que pour onEndWho()
                    $this->onPing($dataregexp);
                    break;
                }

                //Message en provenance d'un serveur ou d'un utilisateur?
                if (!ereg("^.*!.*@.* .*$", $dataregexp)) {
                    if (preg_match('`:(.*?) `', $dataregexp, $server)) {
                        $server = $server[1];
                    }
                } elseif (preg_match('`:(.*?) `', $dataregexp, $userhost)) {
                    $userhost = $userhost[1];
                }

                //Traitement des �v�nements serveur<->bot
                if (isset($server)) {
                    //Numeric 376 - Fin du /MOTD
                    //:proxy.epiknet.org 376 IRPG2 :End of /MOTD command.
                    //if (ereg("^:$server 376", $dataregexp)) {
                    //    $this->onMOTD($dataregexp);
                    //}
                    if (preg_match("/^:$server 376 (.*?) :.*$/", $dataregexp, $me)) {
                        $this->onMOTD($dataregexp, $me[1]);
                    }

                    //Numeric 353 - /names
                    //:proxy.epiknet.org 353 IRPG2 @ #IRPG2 :IRPG2 @Homer
                    //:proxy.epiknet.org 353 IRPG2 = #irpg :IRPG2 %coolman_ +Shelby Suisse[Po`la] %Brendan
                    if (preg_match_all("/^:$server 353 (.*?) (@|=) #(.*?) :(.*?)$/", $dataregexp, $names)) {
                        $this->onNames($names[3][0], $names[4][0]);
                    }

                    //Numeric 352 - /who
                    //:proxy.epiknet.org 352 IRPG2 #IRPG2 Homer server-admin.epiknet.org proxy.epiknet.org --->
                    //---> Homer Hr* :0 Homer - www.iQuotes-FR.com
                    if (preg_match_all("/^:$server 352 $this->me (.*?) (.*?) (.*?) (.*?) (.*?) .*$/",
                        $dataregexp, $who
                    )) {
                        $this->onWho($who[5][0], $who[2][0], $who[3][0], $who[4][0], $who[1][0]);
                    }

                    //Numeric 315 - End of /who
                    //:irc-homer.epiknet.org 315 IRPG2 #IRPG2 :End of /WHO list.
                    if (preg_match("/^:$server 315 (.*?) (.*?) .*$/", $dataregexp, $channel)) {
                        $this->onEndWho($channel[2]);
                    }
                    //if (ereg("^:$server 315.*$", $dataregexp)) {
                    //    $this->onEndWho();
                    //}

                    //Message "ERROR"
                    //if (ereg("^ERROR :.*", $dataregexp)) {
                    //    $irpg->alog("RECEPTION d'un message ERROR, d�connexion du serveur.");
                    //    socket_close($this->sirc);
                    //    return false;
                    //}
                }

                //Traitement des �v�nements utilisateur<->bot
                if (isset($userhost)) {
                    //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

                    //On extrait le $userhost, pour r�cup�rer $Nick, $User et $Host
                    preg_match("/:(.*?)!/", $dataregexp, $nick);
                    $nick = $nick[1];
                    preg_match("/.*!(.*?)@/", $dataregexp, $user);
                    $user = $user[1];
                    preg_match("/@(.*?) /", $dataregexp, $host);
                    $host = $host[1];

                    //On �charpe $userhost pour pas planter les regexp
                    $userhost = preg_quote($userhost);

                    //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

                    //Traitement des PRIVMSG & CTCP
                    if (ereg("^:$userhost PRIVMSG #", $dataregexp)) {
                        /* Sur le canal */
                        //On extrait le message
                        //:Homer!Homer@server-admin.epiknet.org PRIVMSG #IRPG2 :dsgs : dgsdg : gdgdfg : dgsdgsd
                        $message = substr($dataregexp, strpos($dataregexp, ':', 1)+1);
                        $this->onPrivmsgCanal(trim($nick), trim($user), trim($host), trim($message));
                    } elseif (ereg("^:$userhost PRIVMSG ", $dataregexp)) {
                        /* En priv� */
                        // On ne va pas plus loin si le pseudo ou l'host doit �tre ignor� !
                        if ((in_array($nick, $ignoresN)) || (in_array($host, $ignoresH))) {
                            continue;
                        }

                        //PRIVMSG ou CTCP?
                        if (preg_match("/^:$userhost PRIVMSG .* :\001(.*?)\001/", $dataregexp, $ctcp)) {
                            $this->onCTCP(trim($nick), trim($user), trim($host), trim($ctcp[1]));
                        } else {
                            //On extrait le message
                            $message = substr($dataregexp, strpos($dataregexp, ':', 1)+1);
                            $this->onPrivmsgPrive(trim($nick), trim($user), trim($host), trim($message));
                        }
                    }

                    //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

                     //Traitement des NOTICE
                    if (ereg("^:$userhost NOTICE #", $dataregexp)) {
                        /* Sur le canal */
                        //On extrait le message
                        $message = substr($dataregexp, strpos($dataregexp, ':', 1)+1);
                        $this->onNoticeCanal(trim($nick), trim($user), trim($host), trim($message));
                    } elseif (ereg("^:$userhost NOTICE ", $dataregexp)) {
                        /* En priv� */
                        // On ne va pas plus loin si le pseudo ou l'host doit �tre ignor� !
                        if ((in_array($nick, $ignoresN)) || (in_array($host, $ignoresH))) {
                            continue;
                        }

                        //On extrait le message
                        $message = substr($dataregexp, strpos($dataregexp, ':', 1)+1);
                        $this->onNoticePrive(trim($nick), trim($user), trim($host), trim($message));
                    }

                 //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

                 //Traitement du JOIN
                 if (ereg("^:$userhost JOIN :#", $dataregexp)) {
                        //On extrait le canal
                        $channel = split(":", $dataregexp);
                        $channel = $channel[2];
                        $this->onJoin(trim($nick), trim($user), trim($host), trim($channel));
                 }

                 //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

                 //Traitement du PART
                 if (ereg("^:$userhost PART #", $dataregexp)) {
                        //On extrait le canal
                        $channel = split(" ", $dataregexp);
                        $channel = $channel[2];
                        $this->onPart(trim($nick), trim($user), trim($host), trim($channel));
                 }

                 //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

                 //Traitement du NICK
                 if (ereg("^:$userhost NICK :", $dataregexp)) {
                        //On extrait le nouveau nick
                        $newnick = split(":", $dataregexp);
                        $newnick = $newnick[2];
                        $this->onNick(trim($nick), trim($user), trim($host), trim($newnick));
                 }

                 //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

                 //Traitement du KICK
                 if (ereg("^:$userhost KICK #", $dataregexp)) {
                        //On extrait le canal et le kick�
                        $kick = split(" ", $dataregexp);
                        $channel = $kick[2];
                        $nickkicked = $kick[3];
                        $this->onKick(trim($nick), trim($user), trim($host), trim($channel), trim($nickkicked));
                 }

                 //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

                 //Traitement du QUIT
                 if (ereg("^:$userhost QUIT :", $dataregexp)) {
                        //On extrait la raison du QUIT
                        preg_match("/^:$userhost QUIT :(.*?$)/", $dataregexp, $reason);
                        $reason = $reason[1];
                        $this->onQuit(trim($nick), trim($user), trim($host), trim($reason));
                 }

                 //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

                }

                $this->Timer5Sec($dix, $quinze);

                //R�initialisation de variables
                unset($server, $userhost, $message, $newnick, $reason, $nickkicked, $channel);
            }

            //$read = array($this->sirc);
            //socket_select($read, $write = NULL, $except = NULL, 0);
            //if (count($read) > 0) {
            //    $this->nbReadError++;
            //    print "READ ERROR!!: " . socket_strerror(socket_last_error()) . "\n\n";
            //    if ($this->nbReadError > 2) {
            //        $this->deconnexion("ARRRG ! Je viens de rencontrer une erreur fatale :(. Debug: "
            //            . socket_strerror(socket_last_error())
            //        );
            //        break;
            //    }
            //}
        }
    }


///////////////////////////////////////////////////////////////

    /**
     * Destructeur; termine la connexion au serveur IRC
     *
     * @author Homer
     * @created 30 mai 2005
     * @modified 30 mai 2005
     * @param reason   - Raison de la d�connexion
     * @return boolean - true si la d�connexion r�ussie, false sinon.
     */
    function deconnexion($reason)
    {
        $ok = $this->sendRaw("QUIT :$reason");
        if ($ok) {
            socket_close($this->sirc);
            $this->sirc = null;
            $this->exit = true;
            return true;
        } else {
            return false;
        }
    }

///////////////////////////////////////////////////////////////

    /**
     * Envoit un privmsg au destinataire
     *
     * @author Homer
     * @created 20 juin 2005
     * @modified 20 juin 2005
     * @param dest       - Destinataire (un pseudo ou un canal)
     * @param message    - Message � envoyer
     * @return none
     */
    function privmsg($dest, $message)
    {
        $this->sendRaw("PRIVMSG $dest :$message");
    }

///////////////////////////////////////////////////////////////

    /**
     * Envoit une notice au destinataire (ou un privmsg)
     *
     * @author Homer
     * @created 20 juin 2005
     * @modified 20 juin 2005
     * @param dest       - Destinataire (un pseudo ou un canal)
     * @param message    - Message � envoyer
     * @return none
     */
    function notice($dest, $message)
    {
        global $db, $irpg;

        $uid = $irpg->getUIDByUsername($irpg->getUsernameByNick($dest));

        $tbUtil = $db->prefix . "Utilisateurs";
        if ($db->nbLignes("SELECT Notice FROM $tbUtil WHERE Id_Utilisateurs='$uid' And Notice='N'") == 1) {
            $this->sendRaw("PRIVMSG $dest :$message");
        } else {
            $this->sendRaw("NOTICE $dest :$message");
        }
    }

///////////////////////////////////////////////////////////////

    /**
     * V�rifie la pr�sence d'un utilisateur sur un canal
     *
     * @author Homer
     * @created 12 juillet 2005
     * @modified 12 juillet 2005
     * @param channel     - Canal � v�rifier
     * @param nickname    - Pseudo � v�rifier
     * @return boolean    - true si pr�sent, False si absent
     */
    function isOn($channel, $nickname)
    {
        global $db;

        $table = $db->prefix . "IRC";
        if ($db->nbLignes("SELECT Nick FROM $table WHERE Nick='$nickname' And Channel='$channel'") > 0) {
            return true;
        } else {
            return false;
        }
    }

    ///////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////
}
?>
