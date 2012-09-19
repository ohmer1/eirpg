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
* Module mod_test
* Module expérimental IRPG
*
* @author Homer
* @created 19 juin 2005
* @modified 19 juin 2005
*/ 

class test2
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes dépendants
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  
///////////////////////////////////////////////////////////////
  Function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'éxécute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg;
    
    $this->name = "mod_test2";
    $this->version = "0.1.1";
    $this->desc = "Module expérimental 2";
    $this->depend = Array("");
    
    //Recherche de dépendances
    If (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: dépendance non résolue\n");
    }
    
    //Validation du fichier de configuration spécifique au module
    $cfgKeys = Array("");  //Clés obligatoires
    $cfgKeysOpt = Array("");        //Clés optionelles
    If (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": Vérifiez votre fichier de configuration.\n");
    }
    
      
  }
  
///////////////////////////////////////////////////////////////
  Function unloadModule()
  {
    //Destructeur; décharge le module
    //S'éxécute lors du SHUTDOWN du bot ou d'un REHASH
    global $irc, $irpg;

      
    
    /* Placer les instructions de déchargement de module entre ici et la fin */



      
  }
  
///////////////////////////////////////////////////////////////

  Function onConnect() {
    global $irc, $irpg;
    
  }
  
///////////////////////////////////////////////////////////////

  Function onPrivmsgCanal($nick, $user, $host, $message) {
    global $irc, $irpg;
    
  }
  
///////////////////////////////////////////////////////////////


  Function onPrivmsgPrive($nick, $user, $host, $message) {
    global $irc, $irpg;
    /* test */
    //Ajout de la commande UNLOADMODULE
    $message = trim(str_replace("\n", "", $message));
    $message = explode(" ", $message);
    
    If ($message[0] == "UNLOADMODULE") 
    { 
      If ($irpg->unloadModule($message[1]))
      {
        $irc->notice($nick, "Le module a été déchargé");
      }
      Else {
        $irc->notice($nick, "/!\ Le module ne peut être déchargé");
      }
    }
    ElseIf ($message[0] == "LOADMODULE")
    {
      If ($irpg->loadModule($message[1]))
      {
        $irc->notice($nick, "Le module a été chargé avec succès");
      }
      Else { 
        $irc->notice($nick, "/!\ Erreur lors du chargement du module");
      }
      
    }
    ElseIf ($message[0] == "MODULES") {
       print_r($irpg->mod);
       print_r($irpg->modules);
      
    }
    Else {
      $irc->notice($nick, "Commande invalide");
    }
    
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onNoticeCanal($nick, $user, $host, $message) {
    global $irc, $irpg;
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onNoticePrive($nick, $user, $host, $message) {
    global $irc, $irpg;
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onJoin($nick, $user, $host, $channel) {
    global $irc, $irpg;
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onPart($nick, $user, $host, $channel) {
    global $irc, $irpg;
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onNick($nick, $user, $host, $newnick) {
    global $irc, $irpg;
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onKick($nick, $user, $host, $channel, $nickkicked) {
    global $irc, $irpg;
    
  }
    
///////////////////////////////////////////////////////////////

  Function onCTCP($nick, $user, $host, $ctcp) {
    global $irc, $irpg;
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onQuit($nick, $user, $host, $reason) {
    global $irc, $irpg;
    
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
    global $irc, $irpg;
    
  }

/////////////////////////////////////////////////////////////// 
  

}
 
 
 
?>
