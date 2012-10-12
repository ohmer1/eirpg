<?php

/*
EpiKnet Idle RPG (EIRPG)
Copyright (C) 2005-2012 Francis D (Homer) & EpiKnet

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License
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
* Module mod_bonus
* Bonus pour les donateurs IRPG
*
* @author Homer
* @created 1 avril 2007
*/

class bonus
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
    $this->name = "mod_bonus";
    $this->version = "1.0.0";
    $this->desc = "Bonus pour les dondateurs IRPG";
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
      case "BONUS":
        // Retourne la date d'expiration du bonus
          $this->cmdBonus($nick);
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

   // toutes les 6h, il y a une chance sur 4 de lancer le bonus
   if ($irc->ready) {
      if ($this->timer < 1440)
      {
        $this->timer++;
      }
      else
      {
        $this->timer=0;
        if (rand(1, 4) == 1)  $this->lanceBonus();
      }

     }
   }


///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

  Function lanceBonus() {
    global $irpg, $irc, $db;
    $tbPerso = $db->prefix . "Personnages";
    $tbIRC = $db->prefix . "IRC";
    $tbDon = $db->prefix . "Dons";

    //on sélectionne d'abord un personnage en ligne
    $query = "SELECT Id_Personnages, Nom, Level, Next FROM $tbPerso WHERE Id_Personnages IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id)) AND Util_Id IN (SELECT Util_Id FROM $tbDon WHERE Expiration>=NOW()) ORDER BY RAND() LIMIT 0,1";
    if ($db->nbLignes($query) != 1) return false;
    $res = $db->getRows($query);

    $pid = $res[0]['Id_Personnages'];
    $perso = $res[0]['Nom'];
    $level = $res[0]['Level'];
    $level2 = $level + 1;
    $next = $res[0]['Next'];

    //La bonus peut modifier le TTL entre 4 et 9%
    $time = rand(4, 9);


     $time = round($next * ($time/100), 0);
     $ctime = $irpg->convSecondes($time);
     $next = $next - $time;
     $cnext = $irpg->convSecondes($next);
     $db->req("UPDATE $tbPerso SET Next=$next WHERE Id_Personnages='$pid'");
     $irpg->Log($pid, "BONUS_DONATEUR", "", "-$time");
     $irc->privmsg($irc->home, "Le maître de l'idle remercie ses supporteurs et récompense $perso en lui enlevant $ctime avant d'arriver au niveau $level2.  Prochain niveau dans $cnext.");




  }

  Function cmdBonus($nick)
  {
	global $irpg, $irc, $db;

    $uid = $irpg->getUsernameByNick($nick, true);
    $uid = $uid[1];

    $tbDon = $db->prefix . "Dons";

    $expiration = $db->getRows("SELECT Expiration FROM $tbDon WHERE Util_Id='$uid'");
    if (count($expiration) == 1)
    {
	$irc->notice($nick, "Votre compte est en mode bonus jusqu'au " . $expiration[0]["Expiration"] . ".  Pour le prolonger : http://www.eirpg.com/app/index.php5?page=Dons");
    }
    else {
	$irc->notice($nick, "Votre compte n'est pas en mode bonus.  Pour l'activer : http://www.eirpg.com/app/index.php5?page=Dons");
    }
  }

}
?>