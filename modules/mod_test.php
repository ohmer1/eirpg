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
* Module mod_test
* Module expérimental IRPG
*
* @author Homer
* @created 19 juin 2005
* @modified 19 juin 2005
*/

class test  /* Le nom de la classe DOIT être du même nom que le module (sans le mod_) */
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  /* Les variables obligatoires du module */
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes dépendants

  /* Variables supplémentaires à la suite, si nécessaire */

//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////
  function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'éxécute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg;

    /* Renseignement des variables importantes */
    $this->name = "mod_test";              /* Nom du module, préfixé de mod_ */
    $this->version = "0.1.1";              /* Important de mettre la version sous forme x.y.z */
    $this->desc = "Module expérimental";
    $this->depend = array("test2/0.1.1");  /* Syntaxe: nomModule/version (x.y.z) */

    //Recherche de dépendances
    /* Ne pas modifier ce qui suit; procédure de vérification des dépendances */
    if (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: dépendance non résolue\n");
    }

    //Validation du fichier de configuration spécifique au module
    $cfgKeys = array("testparam");  //Clés obligatoires
    $cfgKeysOpt = array("");        //Clés optionelles

    /* Ne pas modifier ce qui suit; lecture et validation du fichier de configuration */
    if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": Vérifiez votre fichier de configuration.\n");
    }


    /*
     * Ajoutez votre programmation à éxécuter lors du
     * chargement du module à partir d'ici
     *
     */


  }

///////////////////////////////////////////////////////////////
  function unloadModule()
  {
    //Destructeur; décharge le module
    //S'éxécute lors du SHUTDOWN du bot ou d'un REHASH
    global $irc, $irpg;


    /* Placer les instructions de déchargement de module entre ici et la fin*/





  }

///////////////////////////////////////////////////////////////

  function onConnect() {
    global $irc, $irpg;
    $testparam = $irpg->readConfig("mod_test", "testparam");
    $irc->privmsg("Homer", "Je viens de me connecter !");
    $irc->notice("Homer", "testparam = $testparam");
  }

///////////////////////////////////////////////////////////////

  function onPrivmsgCanal($nick, $user, $host, $message) {
    global $irc, $irpg;
    $irc->privmsg("Homer", "$nick!$user@$host a dit: $message");
  }

///////////////////////////////////////////////////////////////


  function onPrivmsgPrive($nick, $user, $host, $message) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host m'a dit: $message");
  }

///////////////////////////////////////////////////////////////

  function onNoticeCanal($nick, $user, $host, $message) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a dit en notice: $message");
  }

///////////////////////////////////////////////////////////////

  function onNoticePrive($nick, $user, $host, $message) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host m'a dit en notice: $message");
  }

///////////////////////////////////////////////////////////////

  function onJoin($nick, $user, $host, $channel) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a joint $channel");
  }

///////////////////////////////////////////////////////////////

  function onPart($nick, $user, $host, $channel) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a quitté $channel");
  }

///////////////////////////////////////////////////////////////

  function onNick($nick, $user, $host, $newnick) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a changé de pseudo pour $newnick");
  }

///////////////////////////////////////////////////////////////

  function onKick($nick, $user, $host, $channel, $nickkicked) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a kické $nickkicked de $channel");
  }

///////////////////////////////////////////////////////////////

  function onCTCP($nick, $user, $host, $ctcp) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host m'a fait un CTCP $ctcp");
  }

///////////////////////////////////////////////////////////////

  function onQuit($nick, $user, $host, $reason) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a quitté IRC pour la raison suivante: $reason");
  }

///////////////////////////////////////////////////////////////


}



?>
