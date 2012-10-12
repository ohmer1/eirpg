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
* Module mod_godsends
* Gestion des godsends
*
* @author Homer
* @created 18 mars 2006
*/

class godsends
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
    $this->name = "mod_godsends";
    $this->version = "1.0.0";
    $this->desc = "Calamités";
    $this->depend = array("core/0.5.0");

    //Recherche de dépendances
    if (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: dépendance non résolue\n");
    }

    //Validation du fichier de configuration spécifique au module
    $cfgKeys = array();
    $cfgKeysOpt = array();

    if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
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

  function onConnect() {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onPrivmsgCanal($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////


  function onPrivmsgPrive($nick, $user, $host, $message) {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  function onNoticeCanal($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onNoticePrive($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onJoin($nick, $user, $host, $channel) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onPart($nick, $user, $host, $channel) {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  function onNick($nick, $user, $host, $newnick) {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  function onKick($nick, $user, $host, $channel, $nickkicked) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onCTCP($nick, $user, $host, $ctcp) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onQuit($nick, $user, $host, $reason) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function on5Secondes() {
    global $irc, $irpg;

     //il y a une chance sur 4000 d'avoir une godsends
     if (rand(1, 4000) == 1) $this->cmdGodsends();
  }

///////////////////////////////////////////////////////////////


  function on10Secondes() {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////


  function on15Secondes() {
    global $irc, $irpg, $db;



  }



///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

  function cmdGodsends() {
    global $irpg, $irc, $db;
    $tbPerso = $db->prefix . "Personnages";
    $tbIRC = $db->prefix . "IRC";
    $tbTxt = $db->prefix . "Textes";

    //on sélectionne d'abord un personnage en ligne
    $query = "SELECT Id_Personnages, Nom, Level, Next FROM $tbPerso WHERE Id_Personnages IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id)) ORDER BY RAND() LIMIT 0,1";
    if ($db->nbLignes($query) != 1) return false;
    $res = $db->getRows($query);

    $pid = $res[0]['Id_Personnages'];
    $perso = $res[0]['Nom'];
    $level = $res[0]['Level'];
    $level2 = $level + 1;
    $next = $res[0]['Next'];

    //La godsends peut modifier le TTL entre 5 et 12%
    $time = rand(5, 12);

    //Traitement de la godsends
    $time = round($next * ($time/100), 0);
    $ctime = $irpg->convSecondes($time);
    $next = $next - $time;
    $cnext = $irpg->convSecondes($next);
    $db->req("UPDATE $tbPerso SET Next=$next WHERE Id_Personnages='$pid'");
    $message = $db->getRows("SELECT Valeur FROM $tbTxt WHERE Type='G' ORDER BY RAND() LIMIT 0,1");
    $message = $message[0][0];
    $irc->privmsg($irc->home, "$perso $message.  Cette merveilleuse aide de Dieu accélère sa course vers le niveau $level2 de $ctime.  Prochain niveau dans $cnext.");




  }

}
?>
