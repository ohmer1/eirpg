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
* Module mod_penalites
* Op, halfop ou voice les joueurs
*
* @author Homer
* @created 20 novembre 2005
*/ 

class ohvstatus 
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes dépendants
  
  //Variables supplémentaires
  var $op, $hop, $voice, $oplvl, $hoplvl, $voicelvl;
  
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  
///////////////////////////////////////////////////////////////
  Function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'éxécute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg, $db;
    
    /* Renseignement des variables importantes */
    $this->name = "mod_ohvstatus";              
    $this->version = "1.0.0";              
    $this->desc = "Op, halfop ou voice les joueurs";
    $this->depend = Array("core/0.5.0");  
    
    //Recherche de dépendances
    If (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: dépendance non résolue\n");
    }
    
    //Validation du fichier de configuration spécifique au module
    $cfgKeys = Array("op", "hop", "voice", "oplvl", "hoplvl", "voicelvl");  
    $cfgKeysOpt = Array();        
    
    If (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": Vérifiez votre fichier de configuration.\n");
    }
    
    //Initialisation des paramètres du fich de configuration
    $this->op = $irpg->readConfig($this->name, "op");
    $this->hop = $irpg->readConfig($this->name, "hop");
    $this->voice = $irpg->readConfig($this->name, "voice");
    $this->oplvl = $irpg->readConfig($this->name, "oplvl");
    $this->hoplvl = $irpg->readConfig($this->name, "hoplvl");
    $this->voicelvl = $irpg->readConfig($this->name, "voicelvl");

      
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
      case "UP":
        //Donne le status (op, hop, voice) approprié à l'utilisateur
        $this->cmdUp($nick);
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
    
   
    
  }
  
///////////////////////////////////////////////////////////////

  Function modIdle_onLvlUp($nick, $uid, $pid, $level, $next) {
    $this->Up($nick, $level); 
  }
  
///////////////////////////////////////////////////////////////

  Function modCore_onLogin($nick, $uid, $pid, $level, $next) {
    if (!is_null($pid)) {
      $this->Up($nick, $level); 
    }
  }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

  Function cmdUp($nick) {
    global $db, $irc, $irpg;
    //TODO: Valider qu'avec les persos logués plutôt que les persos enregistrés seulement
    
    $tbPerso = $db->prefix."Personnages";
    $uid = $irpg->getUsernameByNick($nick, true);
    $uid = $uid[1];
    
    if ($uid) {
      $req = "SELECT Level FROM $tbPerso WHERE Util_Id='$uid' ORDER BY Level DESC LIMIT 0,1";
      if ($db->nbLignes($req) == 1) {
        $level = $db->getRows($req);
        $level = $level[0]["Level"];
        $this->Up($nick, $level);
      }
      else {
        $irc->notice($nick, "Vous devez être authentifié sous un personnage pour utiliser cette commande."); 
      }
   }
   else {
      $irc->notice($nick, "Vous devez être authentifié pour utiliser cette commande.");
   }
  }

///////////////////////////////////////////////////////////////

  Function Up($nick, $level) {
    global $irc;
    //TODO: vérifier si l'utilisateur n'est pas flagué NOOP

    if (($this->op == "1") and ($this->oplvl <= $level)) {
      $irc->sendRaw("MODE $irc->home +o $nick");
    }
    elseif (($this->hop == "1") and ($this->hoplvl <= $level)) {
      $irc->sendRaw("MODE $irc->home +h $nick");
    }
    elseif (($this->voice == "1") and ($this->voicelvl <= $level)) {
      $irc->sendRaw("MODE $irc->home +v $nick");
    }   
  }
  
}

?>
