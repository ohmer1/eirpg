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
* Module mod_idle
* Calcul de l'idle des joueurs
* Module indispensable au fonctionnement du jeu.
*
* Méthodes inter-modules crées dans ce module:
* - modIdle_onLvlUp($nick, $uid, $pid, $level2, $next)
*
* @author Homer
* @created 10 septembre 2005
*/

class idle
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes dépendants

  //Variables supplémentaires
  var $idleBase;    //Niveau de base (lu du fichier de config)
  var $expLvlUp;    //Valeur exponentiel de calcul de niveau (lu du fich. de config)

//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////
  Function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'éxécute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg, $db;

    /* Renseignement des variables importantes */
    $this->name = "mod_idle";
    $this->version = "1.0.0";
    $this->desc = "Module calculant l'idle";
    $this->depend = Array("core/0.5.0");

    //Recherche de dépendances
    If (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: dépendance non résolue\n");
    }

    //Validation du fichier de configuration spécifique au module
    $cfgKeys = Array("idleBase", "expLvlUp");
    $cfgKeysOpt = Array("");

    If (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": Vérifiez votre fichier de configuration.\n");
    }

    //Initialisation des paramètres du fich de configuration
    $this->idleBase = $irpg->readConfig($this->name, "idleBase");
    $this->expLvlUp = $irpg->readConfig($this->name, "expLvlUp");

  }

///////////////////////////////////////////////////////////////
  Function unloadModule()
  {
    //Destructeur; décharge le module
    //S'éxécute lors du SHUTDOWN du bot ou d'un REHASH
    global $irc, $irpg, $db;

    $irc->deconnexion("SHUTDOWN: mod_idle a été déchargé!");
    $db->deconnexion();


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

    //On retire 15 secondes à tous les
    //personnages en ligne !
    $tbPerso = $db->prefix."Personnages";
    $tbIRC = $db->prefix."IRC";
    $db->req("UPDATE $tbPerso SET Next=Next-15, Idled=Idled+15 WHERE Id_Personnages IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id))");

    //Level up
    $i = 0;
    $up = $db->getRows("SELECT Id_Personnages, Util_Id, Nom, Level, Class FROM $tbPerso WHERE Next <= '0'");
    While ($i != count($up))
    {
      $pid = $up[$i]["Id_Personnages"];
      $uid = $up[$i]["Util_Id"];
      $nomPerso = $up[$i]["Nom"];
      $level = $up[$i]["Level"];
      $level2 = $level + 1;
      $class = $up[$i]["Class"];

      $nick = $irpg->getNickByUID($uid);

      //Calcul du nombre de seconde à idler pour atteindre
      //le prochain niveau
      $next = round($this->idleBase * pow($this->expLvlUp,$level2), 0);

      $db->req("UPDATE $tbPerso SET Level=Level+1, Next='$next' WHERE Id_Personnages='$pid'");
      $irpg->Log($pid, "LEVEL_UP", "0", $level, $level2);

      $cnext = $irpg->convSecondes($next);

      $irc->notice($nick, "Votre personnage $nomPerso vient d'obtenir le niveau $level2 !  Prochain niveau dans $cnext.");
      $irc->privmsg($irc->home, "UP!  $nomPerso, $class vient d'obtenir le niveau $level2 !  Prochain niveau dans $cnext.");

      $y = 0;
      While ($y != count($irpg->mod))
      {
        If (method_exists($irpg->mod[$irpg->modules[$y]], "modIdle_onLvlUp"))
        {
          $irpg->mod[$irpg->modules[$y]]->modIdle_onLvlUp($nick, $uid, $pid, $level2, $next);
        }
        $y++;
      }

      $i++;

    }

  }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////


}

?>
