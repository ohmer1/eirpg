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
* Module mod_hog
* Gestion de la main de Dieu
*
* @author Homer
* @created 11 mars 2006
*/
class hog
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes dépendants

  //Variables supplémentaires

//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////

  function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'éxécute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg, $db;

    /* Renseignement des variables importantes */
    $this->name = "mod_hog";
    $this->version = "1.0.0";
    $this->desc = "Main de Dieu";
    $this->depend = array("core/0.5.0");

    //Recherche de dépendances
    if (!$irpg->checkDepd($this->depend)) {
      die("$this->name: dépendance non résolue\n");
    }

    //Validation du fichier de configuration spécifique au module
    $cfgKeys = array();
    $cfgKeysOpt = array();

    if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
      die ($this->name.": Vérifiez votre fichier de configuration.\n");
    }

    //Initialisation des paramètres du fich de configuration
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

    switch (strtoupper($message[0])) {
      case "HOG":
        //Invoque la main de Dieu (ADMIN)
        $uid = $irpg->getUsernameByNick($nick, true);
        if ($irpg->getAdminLvl($uid[1]) >= 10) {
          $this->cmdHog($nick);
        } else {
          $irc->notice($nick, "Désolé, vous n'avez pas accès à la commande HOG.") ;
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

    if ($irc->ready) {
      //il y a une chance sur 3000 d'invoquer la main de dieu..
      if (rand(1, 3000) == 1) {
        $this->cmdHog();
      }
    }
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

  function cmdHog($nick = "")
  {
    global $irpg, $irc, $db;

    $tbPerso = $db->prefix . "Personnages";
    $tbIRC = $db->prefix . "IRC";

    //on sélectionne d'abord un personnage en ligne
    $query = "SELECT Id_Personnages, Nom, Level, Next FROM $tbPerso WHERE Id_Personnages IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id)) ORDER BY RAND() LIMIT 0,1";
    if ($db->nbLignes($query) != 1) {
      return false;
    }
    $res = $db->getRows($query);

    $pid = $res[0]['Id_Personnages'];
    $perso = $res[0]['Nom'];
    $level = $res[0]['Level'];
    $level2 = $level + 1;
    $next = $res[0]['Next'];

    //La hog peut modifier le TTL entre 5 et 75%
    $time = rand(5, 75);

    if (!empty($nick)) {
      $irc->privmsg($irc->home, "$nick a invoqué la main de Dieu...");
    }

    //Il y a 80% de chance que la hog soit positive
    //et 20% qu'elle soit négative pour le personnage..
    if (rand(1, 5) <= 4) {
      //hog positive
      $time = round($next * ($time/100), 0);
      $ctime = $irpg->convSecondes($time);
      $next = $next - $time;
      $cnext = $irpg->convSecondes($next);
      $db->req("UPDATE $tbPerso SET Next=$next WHERE Id_Personnages='$pid'");
      $irc->privmsg($irc->home, "Dieu s'est levé du bon pied ce matin et décide d'aider $perso en lui enlevant $ctime avant d'arriver au niveau $level2.  Prochain niveau dans $cnext.");
    } else {
      //hog négative
      $time = round($next * ($time/100), 0);
      $ctime = $irpg->convSecondes($time);
      $next = $next + $time;
      $cnext = $irpg->convSecondes($next);
      $db->req("UPDATE $tbPerso SET Next=$next WHERE Id_Personnages='$pid'");
      $irc->privmsg($irc->home, "Dieu en a marre de ne plus vous voir à l'Église et se venge sur $perso en lui ajoutant $ctime avant d'arriver au niveau $level2.  Prochain niveau dans $cnext.");
    }
  }

///////////////////////////////////////////////////////////////
}
?>
