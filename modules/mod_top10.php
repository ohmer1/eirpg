<?php

/*
EpiKnet Idle RPG (EIRPG)
Copyright (C) 2005-2012 Francis D (Homer) & EpiKnet

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License,
version 3 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
* Module mod_top10
* Retourne le top10 des joueurs
*
* @author Homer
* @created 20 janvier 2007
*/

class top10
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes dépendants

  //Variables supplémentaires
  var $timer;

//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////
  Function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'éxécute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg, $db;

    /* Renseignement des variables importantes */
    $this->name = "mod_top10";
    $this->version = "1.0.0";
    $this->desc = "Top10 des joueurs";
    $this->depend = Array("core/0.5.0");

    //Recherche de dépendances
    If (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: dépendance non résolue\n");
    }

    //Validation du fichier de configuration spécifique au module
    $cfgKeys = Array();
    $cfgKeysOpt = Array();

    If (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": Vérifiez votre fichier de configuration.\n");
    }

    //Initialisation des paramètres du fich de configuration
    $this->timer = 0;


  }

///////////////////////////////////////////////////////////////
  Function unloadModule()
  {
    //Destructeur; décharge le module
    //S'éxécute lors du SHUTDOWN du bot ou d'un REHASH
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  Function onConnect() {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  Function onPrivmsgCanal($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////


  Function onPrivmsgPrive($nick, $user, $host, $message) {
    global $irc, $irpg, $db;


    $message = trim(str_replace("\n", "", $message));
    $message = explode(" ", $message);
    $nb = count($message) - 1;

    switch (strtoupper($message[0])) {
      case "TOP":
        $uid = $irpg->getUsernameByNick($nick, true);
        if ($irpg->getAdminLvl($uid[1]) >= 1) {
          If ($nb == 0) { $this->top(); }
          Else { $this->top($message[1]); }
        }
        else {
        	$irc->notice($nick, "Désolé, vous n'avez pas accès à cette commande.");
        }
        break;
    }
  }

///////////////////////////////////////////////////////////////

  Function onNoticeCanal($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  Function onNoticePrive($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  Function onJoin($nick, $user, $host, $channel) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  Function onPart($nick, $user, $host, $channel) {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  Function onNick($nick, $user, $host, $newnick) {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  Function onKick($nick, $user, $host, $channel, $nickkicked) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  Function onCTCP($nick, $user, $host, $ctcp) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  Function onQuit($nick, $user, $host, $reason) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  Function on5Secondes() {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////


  Function on10Secondes() {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////


  Function on15Secondes() {
    global $irc, $irpg, $db;

    // on affiche le top10 une fois toutes les 3 heures
    if ($this->timer < 720)
    {
    	$this->timer++;
    }
    else
    {
    	$this->timer=0;
      $this->top(10);
    }

  }

///////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

  Function top($nb = 10) {
    global $irpg, $irc, $db;

    $res = $db->getRows("SELECT Nom, Class, Level, Next FROM Personnages ORDER BY Level DESC, Next ASC LIMIT $nb");
    $i=0;
    $irc->privmsg($irc->home, "Top $nb des meilleurs idlers :");
    while ($i != count($res))
    {
      $msg = "#" . ($i+1) . " " . $res[$i]["Nom"] . ", " . $res[$i]["Class"] . " de niveau " . $res[$i]["Level"] . ".  Prochain niveau dans " . $irpg->convSecondes($res[$i]["Next"]) . ".";
      $irc->privmsg($irc->home, $msg);
      $i++;
    }

  }


}
?>
