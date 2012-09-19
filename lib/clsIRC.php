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

class IRC
/**
* Classe IRC; gère tout le côté IRC du bot
*
* @author Homer
* @created 30 mai 2005
* @modified 23 juillet 2005
*/ 

{

///////////////////////////////////////////////////////////////	
// Variables privées
///////////////////////////////////////////////////////////////
  var $sirc;      //Socket vers le serveur IRC
  var $debug;     //Flag pour les informations de débuguage
  var $users;     //Utilisateurs connectés sur les canaux
  var $me;        //Pseudo du bot sur IRC
  var $home;      //Canal principal du bot
  var $ready;     //Prêt à démarrer !
  var $lastData ; //Timestamp UNIX du moment où une donnée a été reçu pour la dern fois
  var $exit;      //Coupe la connexion IRC et coupe le bot !
///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////
// Méthodes privées
// Mais PHP ne semble pas faire la différence entre
// privé et publique :)
///////////////////////////////////////////////////////////////

/* Méthodes évènementielles */

  Function onPing($data) {
    $idping = split(":", $data);
    $this->sendRaw("PONG $idping[1]");
  }
    
///////////////////////////////////////////////////////////////

  Function onMOTD($data, $me) {
    global $irpg;
    $channel = strtoupper($irpg->readConfig("IRC","channel"));
    $key = $irpg->readConfig("IRC", "key");
    $modes = $irpg->readConfig("IRC", "modes");
    $nspass = $irpg->readConfig("IRC", "nspass");
    
    //Qui suis-je?
    $this->me = $me;
    
    //Où suis-je?
    $this->home = $channel;
    
    //Applications des modes
    $this->sendRaw("UMODE2 $modes");
    
    //Identification à NS et on patiente 1 secondes
    If (!empty($nspass))
    {
      $this->sendRaw("NICKSERV IDENTIFY $nspass");
      sleep(1);
    }
    
    //On joint ensuite le canal principal
    $this->join($channel, $key);


    //Appel aux modules
    global $irpg; 
    $i = 0;
    While ($i != count($irpg->mod))
    { 
      If (method_exists($irpg->mod[$irpg->modules[$i]], "onConnect"))
      { 
        $irpg->mod[$irpg->modules[$i]]->onConnect();
      }
      $i++;  
    }

    
    //On est maintenant prêt !
    $this->ready = true;
  }
  
///////////////////////////////////////////////////////////////

  Function onPrivmsgCanal($nick, $user, $host, $message) {
    
    If (!$irpg->pause)
    {
      //Appel aux modules
      global $irpg;
      $i = 0;
      While ($i != count($irpg->mod))
      { 
        If (method_exists($irpg->mod[$irpg->modules[$i]], "onPrivmsgCanal"))
        { 
          $irpg->mod[$irpg->modules[$i]]->onPrivmsgCanal(addslashes($nick), $user, $host, $message);
        }
        $i++;  
      }
    }
    
    
  }
  
///////////////////////////////////////////////////////////////


  Function onPrivmsgPrive($nick, $user, $host, $message) {
        
    If (!$irpg->pause)
    {
      //Appel aux modules
      global $irpg;
      $i = 0;
      While ($i != count($irpg->mod))
      { 
        If (method_exists($irpg->mod[$irpg->modules[$i]], "onPrivmsgPrive"))
        {
          $irpg->mod[$irpg->modules[$i]]->onPrivmsgPrive($nick, $user, $host, $message);
        }
        $i++;  
      }
    }
    
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onNoticeCanal($nick, $user, $host, $message) {
        
    If (!$irpg->pause)
    {
      //Appel aux modules
      global $irpg;
      $i = 0;
      While ($i != count($irpg->mod))
      { 
        If (method_exists($irpg->mod[$irpg->modules[$i]], "onNoticeCanal"))
        { 
          $irpg->mod[$irpg->modules[$i]]->onNoticeCanal($nick, $user, $host, $message);
        }
        $i++;  
      }
    }
    
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onNoticePrive($nick, $user, $host, $message) {
        
    If (!$irpg->pause)
    {
      //Appel aux modules
      global $irpg;
      $i = 0;
      While ($i != count($irpg->mod))
      { 
        If (method_exists($irpg->mod[$irpg->modules[$i]], "onNoticePrive"))
        { 
          $irpg->mod[$irpg->modules[$i]]->onNoticePrive($nick, $user, $host, $message);
        }
        $i++;  
      }
    }
    
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onJoin($nick, $user, $host, $channel) {
        
    If (!$irpg->pause)
    {
      //Appel aux modules
      global $irpg;
      $i = 0;
      While ($i != count($irpg->mod))
      { 
        If (method_exists($irpg->mod[$irpg->modules[$i]], "onJoin"))
        { 
          $irpg->mod[$irpg->modules[$i]]->onJoin($nick, $user, $host, $channel);
        }
        $i++;  
      }
    }
    
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onPart($nick, $user, $host, $channel) {
        
    If (!$irpg->pause)
    {
      //Appel aux modules
      global $irpg;
      $i = 0;
      While ($i != count($irpg->mod))
      { 
        If (method_exists($irpg->mod[$irpg->modules[$i]], "onPart"))
        { 
          $irpg->mod[$irpg->modules[$i]]->onPart($nick, $user, $host, $channel);
        }
        $i++;  
      }
    }
    
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onNick($nick, $user, $host, $newnick) {
        
    If (!$irpg->pause)
    {
      //Appel aux modules
      global $irpg;
      $i = 0;
      While ($i != count($irpg->mod))
      { 
        If (method_exists($irpg->mod[$irpg->modules[$i]], "onNick"))
        { 
          $irpg->mod[$irpg->modules[$i]]->onNick($nick, $user, $host, $newnick);
        }
        $i++;  
      }
    }
    
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onKick($nick, $user, $host, $channel, $nickkicked) {
        
    If (!$irpg->pause)
    {
      //Appel aux modules
      global $irpg;
      $i = 0;
      While ($i != count($irpg->mod))
      { 
        If (method_exists($irpg->mod[$irpg->modules[$i]], "onKick"))
        { 
          $irpg->mod[$irpg->modules[$i]]->onKick($nick, $user, $host, $channel, $nickkicked);
        }
        $i++;  
      }
    }
    
    
  }
    
///////////////////////////////////////////////////////////////

  Function onCTCP($nick, $user, $host, $ctcp) {
    
    global $irpg;
    
    //Réponse au CTCP VERSION
    If ($ctcp == "VERSION")
    {
      /* Ne pas modifier ici, sinon BOOM! :) */
      $version = $irpg->readConfig("IRPG", "version");
      $this->sendRaw("NOTICE $nick :\001VERSION EIRPG v$version; http://www.eirpg.com\001");
      
      //Liste des modules chargés
      $i = 0;
      While ($i != count($irpg->mod)) {
        if (empty($modules)) {
          $modules = $irpg->modules[$i] . "/" . $irpg->mod[$irpg->modules[$i]]->version;
        }
        else {
          $modules = $modules . ", " . $irpg->modules[$i] . "/" . $irpg->mod[$irpg->modules[$i]]->version;
        }
        
        $i++;
      }
      $this->sendRaw("NOTICE $nick :\001VERSION Modules chargés: $modules\001");
    }
    
    
        
    //Appel aux modules
    $i = 0;
    While ($i != count($irpg->mod))
    { 
      If (method_exists($irpg->mod[$irpg->modules[$i]], "onCTCP"))
      { 
        $irpg->mod[$irpg->modules[$i]]->onCTCP($nick, $user, $host, $ctcp);
      }
      $i++;  
    }
    
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onQuit($nick, $user, $host, $reason) {
        
    If (!$irpg->pause)
    {
      //Appel aux modules
      global $irpg;
      $i = 0;
      While ($i != count($irpg->mod))
      { 
        If (method_exists($irpg->mod[$irpg->modules[$i]], "onQuit"))
        { 
          $irpg->mod[$irpg->modules[$i]]->onQuit($nick, $user, $host, $reason);
        }
        $i++;  
      }
    }
    
    
  }

///////////////////////////////////////////////////////////////

  Function onNames($channel, $names)
  { 
     global $db;
     $table = $db->prefix."IRC";    
     
     /* 
      * DEPLACE -- voir onWHO()
     $names = split(" ", $names);
     $i = 0;
     While ($i != count($names))
     {
        $names[$i] = ltrim($names[$i], "@");
        $names[$i] = ltrim($names[$i], "%");
        $names[$i] = ltrim($names[$i], "+");
        $names[$i] = trim($names[$i]);
        
        If ((!empty($names[$i])) and ($names[$i] != $this->me))
        {
          $db->req("INSERT INTO $table (`Nick`, `Channel`) VALUES ('$names[$i]', '#$channel')");
        }
        
        $i++;
     }
     */     
     
     //On envoit un /WHO pour récupérer les user@hosts de
     //tous les utilisateurs et permettre l'auto-login
     $this->sendRaw("WHO #$channel");
     
  }
  
///////////////////////////////////////////////////////////////

  Function onWho($nick, $user, $host, $server, $channel)
  {
    global $db, $irpg;
    
    $tbIRC = $db->prefix."IRC";
    $channel = strtoupper($channel);
        
    If ($nick != $this->me) 
    {
      $db->req("INSERT INTO $tbIRC (`Nick`, `Channel`, `UserHost`) VALUES ('$nick', '$channel', '$user@$host')");
    }
    
    
    //Gestion auto-login
    If (($channel == $this->home) And ($nick != $this->me))
    {
      $tbPerso = $db->prefix."Personnages"; 
      $tbUtil = $db->prefix."Utilisateurs";
      
      $query = "SELECT Pers_Id FROM $tbIRC WHERE Nick='$nick' And UserHost='$user@$host' And NOT ISNULL(Pers_Id) And Channel='$channel'"; 
      $nb = $db->nbLignes($query);
      
      If ($nb >= 1)
      {
        //L'utilisateur peut donc être relogué automatiquement
        $username = $db->getRows("SELECT Username FROM $tbUtil WHERE Id_Utilisateurs = (SELECT Util_Id FROM $tbPerso WHERE Id_Personnages = ($query LIMIT 0,1))");
        $username = $username[0]["Username"];
        
        $persodb = $db->getRows($query);
        $i = 0;
        While ($i != count($persodb)) 
        {
          $irpg->mod["core"]->autologged[] = Array($username, $persodb[$i]["Pers_Id"], $nick, "$user@$host"); 
          $i++;
        }
        
        $irpg->mod["core"]->users["$username"] = $nick;
        
      }
    }
  }
  
///////////////////////////////////////////////////////////////

  Function onEndWho($channel)
  {  
    $channel = strtoupper($channel);
    
    If ($channel == $this->home)
    {
      global $irc, $db, $irpg, $nbExecute;
      
      If (empty($nbExecute)) { $nbExecute = 0; }
      
      If ($nbExecute == 0)
      {
        //On vire tous les persos identifiés de la table IRC
        $table = $db->prefix."IRC";
        $db->req("DELETE FROM $table WHERE Not ISNULL(Pers_Id)");
      }
      
      //Maintenant, on réinsert les persos autorelogués
      //via les résultats du /who
      $i = 0;
      While ($i != count($irpg->mod["core"]->autologged)) {
        $auto = $irpg->mod["core"]->autologged[$i];
        
        if ($auto[1] != "") {
          $db->req("INSERT INTO $table (`Pers_Id`, `Nick`, `UserHost`, `Channel`) VALUES ('$auto[1]', '$auto[2]', '$auto[3]', '$this->home')");
          
          $perso = $irpg->getNomPersoByPID($auto[1]);
          
          If ($i == (count($irpg->mod["core"]->autologged) - 1))
          {
            //C'est le dernier joueur
            $lstAuto = $lstAuto.$perso;
          }
          Else 
          {
            $lstAuto = $lstAuto."$perso, ";
          }
        }
        $i++;
      }
      
      If (($i == 1) and (!empty($lstAuto))) 
      {
        $this->privmsg($this->home, "Le personnage suivant a été automatiquement relogué : $lstAuto.");
      }
      ElseIf (($i > 1)  and (!empty($lstAuto))) 
      {
      $this->privmsg($this->home, "Les personnages suivants ont été automatiquement relogués : $lstAuto.");
      }
      
      $irpg->mod["core"]->resetAutoLogin();
      
      $nbExecute++;
    }
  }

///////////////////////////////////////////////////////////////


/* Autres méthodes */
  Function Timer5Sec(&$dix, &$quinze) 
  {
    global $irpg, $db, $last5sec;
    
    If ((($last5sec + 5) <= time()) And ($this->ready)) {
      If (!$irpg->pause)
      {
        //Appel aux modules
        $i = 0;
        While ($i != count($irpg->mod))
        { 
          If (method_exists($irpg->mod[$irpg->modules[$i]], "on5Secondes"))
          { 
            $irpg->mod[$irpg->modules[$i]]->on5Secondes();
          }
          $i++;  
        }
      }
      
      $last5sec = time();
      $dix++;
      $quinze++;
        
        If ($dix == 2) {
          If (!$irpg->pause)
          {
            //Appel aux modules
            $i = 0;
            While ($i != count($irpg->mod))
            { 
              If (method_exists($irpg->mod[$irpg->modules[$i]], "on10Secondes"))
              { 
                $irpg->mod[$irpg->modules[$i]]->on10Secondes();
              }
              $i++;  
            }
          }
          $dix = 0;
        }
          
        If ($quinze == 3) {
          If (!$irpg->pause)
          {
            //Appel aux modules
            $i = 0;
            While ($i != count($irpg->mod))
            { 
              If (method_exists($irpg->mod[$irpg->modules[$i]], "on15Secondes"))
              { 
                $irpg->mod[$irpg->modules[$i]]->on15Secondes();
              }
              $i++;  
            }
          }
          $quinze = 0;
            
          //Si la connexion DB est perdu; on retente une 
          //nouvelle connexion toutes les 15 secondes..
          If ((!$db->connected) and (!$this->exit))
          { 
            If ($db->connexion($irpg->readConfig("SQL", "host"), $irpg->readConfig("SQL", "login"), $irpg->readConfig("SQL", "password"), $irpg->readConfig("SQL", "base"), $irpg->readConfig("SQL", "prefix")))
            { 
              
              //On réinitialise notre table IRC et objets, comme si le bot venait
              //d'être redémarré
              $irpg->mod["core"]->users = Array();
              $irpg->mod["core"]->autologged = Array();
              
              $table = $db->prefix."IRC";
              $channel = $irpg->readConfig("IRC", "channel");
              $db->req("DELETE FROM $table WHERE ISNULL(Pers_Id)");
              
              //On envoit un NAMES pour s'assurer d'avoir
              //une bd à jour..
              $this->sendRaw("NAMES $this->home");  //TODO: Gérer le multi-chans
              
              $this->privmsg($this->home, "La connexion au serveur de bases de données a été rétablie.  Le jeu est de nouveau actif.  Bon idle !"); 
              $irpg->pause = false; //C'est reparti !
            } 
          }
        }
    }
  }
  
///////////////////////////////////////////////////////////////

  Function checkTimeout() {
    if ($this->lastData+180 < mktime()) {
      $this->deconnexion("Perte de connexion vers le serveur IRC... reconnexion.");
      return true; 
    }
    else {
      return false;
    }
  }
  
///////////////////////////////////////////////////////////////

  Function updateTimeout() {
    $this->lastData = mktime(); 
  }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////
// Méthodes publiques
///////////////////////////////////////////////////////////////
  Function sendRaw($data)
  
  /**
  * Envoi des données au serveur IRC
  *
  * @author Homer
  * @created 30 mai 2005
  * @modified 19 juin 2005
  * @param data     - Données à envoyer
  * @return boolean - True si l'envoi a réussi', false sinon.
  */
  {
    global $irpg;
    
    If ($this->debug) { $irpg->alog("<-- $data"); }
   // $charset = $irpg->readConfig("IRC", "charset");
    //$ok = socket_write($this->sirc, iconv("ISO-8859-15", $charset, $data ."\n"));
    $ok = socket_write($this->sirc, $data ."\n");
    If ($ok)
    {
      return true;
    } Else {
      return false;
    }
  }
  
///////////////////////////////////////////////////////////////

  Function join($channel, $key = "")
  /**
  * JOIN : join un canal IRC
  *
  * @author Homer
  * @created 30 mai 2005
  * @modified 30 mai 2005
  * @param channel  - Canal à joindre
  * @param key      - Clé du canal à joindre (facultatif)
  * @return boolean - True si la déconnexion réussie, false sinon.
  */ 
  
  {
    $ok = $this->sendRaw("JOIN $channel $key");
    If ($ok) 
    {
      return true;
    } Else {
      return false;
    }
  }
  

///////////////////////////////////////////////////////////////

  Function connexion($server, $port, $user, $realname, $nick, $bind, $pass = "", $debug = false)
  /**
  * Constructeur; connexion à un serveur IRC
  *
  * @author Homer
  * @created 30 mai 2005
  * @modified 1er juin 2005
  * @param server 	- Adresse du serveur IRC
  * @param port 		- Port de connexion au serveur
  * @param realname - Realname du bot
  * @param nick			- Pseudo du bot
  * @param pass 		- Mot de passe de connexion au serveur (facultatif)
  * @param debug 		- Flag debug
  * @return boolean - True si connexion réussie, false sinon.
  */ 
	
  {
    
    global $irpg;
    
    $this->debug = $debug;
    $this->lastData = mktime();
    $this->exit = false;
    
    $this->sirc = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)
     or die ("Impossible de créer le socket IRC\n");
     
    If ($bind != "") { socket_bind($this->sirc, $bind); }
       
    socket_connect($this->sirc, $server, $port)
     or die ("Impossible de se connecter au serveur IRC\n");
        
    
    If ($this->sirc)
    {	
      
      $irpg->alog("Connexion au serveur IRC...", true);
      If ($pass != "") { $this->sendRaw("PASS $pass"); } //Mot de passe d'accès au serveur
      $this->sendRaw("NICK $nick");
      $ok = $this->sendRaw("USER $user localhost $server :$realname");

      return true;
      
     
			
   } Else {
      return false;
   }
		
	}

///////////////////////////////////////////////////////////////

  Function boucle()
  /**
  * Méthode d'écoute du socket IRC
  * Boucle tant que le socket est ouvert
  *
  * @author Homer
  * @created 20 juin 2005
  * @modified 20 juin 2005
  * @param none
  * @return none
  */ 
  {
    global $irpg;
    
    $irpg->alog("Démarré avec succès.", true);
    $last5sec = time();
    $dix = 0;
    $quinze = 0;
    socket_set_nonblock($this->sirc);  
    $charset = $irpg->readConfig("IRC", "charset");
    
    // lecture des ignores
    $ignoresN = $irpg->getIgnoresN();
    $ignoresH = $irpg->getIgnoresH();
    
    while(true)
    {       
      if ($this->exit) { break; }
    
      $buf = @socket_read($this->sirc, 4096);
      // l'encadage interne du bot est en ISO-8859-15, il faut donc convertir ce qui vient d'IRC en ISO
      //$buf = iconv($charset, "ISO-8859-15", $buf);
      
      If (empty($buf))
      {
        $this->Timer5Sec($dix, $quinze);
        if ($this->checkTimeout()) { break; }
        sleep(1); //Sans sleep, on bouffe 100% du CPU..
        continue;
      }
      
      $this->updateTimeout();
      
      If (!strpos($buf, "\n"))
      { //Si ne contient aucun retour, on bufferise
        $buffer = $buffer.$buf; 
        $data = ""; //rien à envoyer
      }
      Else
      { 
        //Si contient au moins un retour,
        //on vérifie que le dernier caractère en est un
        If (substr($buf, -1, 1)  == "\n")
        { 
          //alors on additionne ces données au buffer
          $data = $buffer.$buf;
          $buffer = ""; //on vide le buffer
        }
        Else 
        { 
          //si le dernier caractère n'est pas un retour à la
          //ligne, alors on envoit tout jusqu'au dernier retour
          //puis on bufferise le reste
          $buffer = $buffer.substr($buf, strrchr($buf, "\n"));
          $data = substr($buf, 0, strrchr($buf, "\n"));
          $data = $buffer.$data;
          $buffer = ""; //on vide le buffer
        }
      }
      
    
      $data = split("\n", $data);    
        
      for ($i = 0; $i < count($data); $i++)
      {   
        If ($this->debug) { $irpg->alog("--> $data[$i]"); }
                
        //On écharpe notre $data[$i] pour le traitement par regexp
        $dataregexp = addslashes($data[$i]);
        //$dataregexp = preg_quote($data[$i]);        
                
        //Ping! Pong!
        If (ereg("^PING ", $dataregexp))
        {
          global $nbExecute;
          unset($nbExecute); //Reset de la variable qui ne sert que pour onEndWho()
          $this->onPing($dataregexp); break;
        }
        
        //Message en provenance d'un serveur ou d'un utilisateur?
        If (!ereg("^.*!.*@.* .*$", $dataregexp))
        {
          preg_match('`:(.*?) `', $dataregexp, $server);
          $server = $server[1];
        }
        Else
        { 
          preg_match('`:(.*?) `', $dataregexp, $userhost);
          $userhost = $userhost[1];
        } 
      

        //Traitement des évènements serveur<->bot
        If (isset($server))
        {
          //Numeric 376 - Fin du /MOTD
          #:proxy.epiknet.org 376 IRPG2 :End of /MOTD command.
          //If (ereg("^:$server 376", $dataregexp)) { $this->onMOTD($dataregexp); }
          If (preg_match("/^:$server 376 (.*?) :.*$/", $dataregexp, $me)) { $this->onMOTD($dataregexp, $me[1]); }
          
          //Numeric 353 - /names
          #:proxy.epiknet.org 353 IRPG2 @ #IRPG2 :IRPG2 @Homer
          #:proxy.epiknet.org 353 IRPG2 = #irpg :IRPG2 %coolman_ +Shelby Suisse[Po`la] %Brendan
          If (preg_match_all("/^:$server 353 (.*?) (@|=) #(.*?) :(.*?)$/", $dataregexp, $names)) { $this->onNames($names[3][0], $names[4][0]); }
          
          //Numeric 352 - /who
          #:proxy.epiknet.org 352 IRPG2 #IRPG2 Homer server-admin.epiknet.org proxy.epiknet.org Homer Hr* :0 Homer - www.iQuotes-FR.com
          If (preg_match_all("/^:$server 352 $this->me (.*?) (.*?) (.*?) (.*?) (.*?) .*$/", $dataregexp, $who)) { $this->onWho($who[5][0], $who[2][0], $who[3][0], $who[4][0], $who[1][0]); }
          
          //Numeric 315 - End of /who
          #:irc-homer.epiknet.org 315 IRPG2 #IRPG2 :End of /WHO list.
          If (preg_match("/^:$server 315 (.*?) (.*?) .*$/", $dataregexp, $channel)) { $this->onEndWho($channel[2]); }
          #If (ereg("^:$server 315.*$", $dataregexp)) { $this->onEndWho(); }
          
          //Message "ERROR"
          #If (ereg("^ERROR :.*", $dataregexp)) {
          #  $irpg->alog("RECEPTION d'un message ERROR, déconnexion du serveur.");
          #  socket_close($this->sirc);
          #  return false;
          #}
        }
        
       
        //Traitement des évènements utilisateur<->bot
        If (isset($userhost))
        {
          //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
          
          //On extrait le $userhost, pour récupérer $Nick, $User et $Host
          preg_match("/:(.*?)!/", $dataregexp, $nick);
          $nick = $nick[1];
          preg_match("/.*!(.*?)@/", $dataregexp, $user);
          $user = $user[1]; 
          preg_match("/@(.*?) /", $dataregexp, $host);
          $host = $host[1];
          
          //On écharpe $userhost pour pas planter les regexp
          $userhost = preg_quote($userhost);
          
          //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
                 
          //Traitement des PRIVMSG & CTCP
          If (ereg("^:$userhost PRIVMSG #", $dataregexp))
          { 
            /* Sur le canal */
            //On extrait le message
            #:Homer!Homer@server-admin.epiknet.org PRIVMSG #IRPG2 :dsgs : dgsdg : gdgdfg : dgsdgsd
            $message = substr($dataregexp, strpos($dataregexp, ':', 1)+1);
            $this->onPrivmsgCanal(trim($nick), trim($user), trim($host), trim($message)); 
          }
          ElseIf (ereg("^:$userhost PRIVMSG ", $dataregexp))
          {
            /* En privé */
            
            // On ne va pas plus loin si le pseudo ou l'host doit être ignoré !
            if ((in_array($nick, $ignoresN)) or (in_array($host, $ignoresH))) 
            {
              continue;
            }
            
            //PRIVMSG ou CTCP?
            If (preg_match("/^:$userhost PRIVMSG .* :\001(.*?)\001/", $dataregexp, $ctcp))
            { 
              $this->onCTCP(trim($nick), trim($user), trim($host), trim($ctcp[1]));
            }
            Else {
              //On extrait le message
              $message = substr($dataregexp, strpos($dataregexp, ':', 1)+1);
              $this->onPrivmsgPrive(trim($nick), trim($user), trim($host), trim($message));
            }
          }
          
          //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
          
           //Traitement des NOTICE
          If (ereg("^:$userhost NOTICE #", $dataregexp))
          {
            /* Sur le canal */
            //On extrait le message
            $message = substr($dataregexp, strpos($dataregexp, ':', 1)+1);
            $this->onNoticeCanal(trim($nick), trim($user), trim($host), trim($message)); 
          }
          ElseIf (ereg("^:$userhost NOTICE ", $dataregexp))
          {
            /* En privé */
            // On ne va pas plus loin si le pseudo ou l'host doit être ignoré !
            if ((in_array($nick, $ignoresN)) or (in_array($host, $ignoresH))) 
            {
              continue;
            }
            
            //On extrait le message
            $message = substr($dataregexp, strpos($dataregexp, ':', 1)+1);
            $this->onNoticePrive(trim($nick), trim($user), trim($host), trim($message));
          }
          
         //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
         
         //Traitement du JOIN
         If (ereg("^:$userhost JOIN :#", $dataregexp))
         {
            #On extrait le canal
            $channel = split(":", $dataregexp);
            $channel = $channel[2];
            $this->onJoin(trim($nick), trim($user), trim($host), trim($channel)); 
         }
          
         //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
         
         //Traitement du PART
         If (ereg("^:$userhost PART #", $dataregexp))
         {
            #On extrait le canal
            $channel = split(" ", $dataregexp);
            $channel = $channel[2];
            $this->onPart(trim($nick), trim($user), trim($host), trim($channel)); 
         }
         
         //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
         
         //Traitement du NICK
         If (ereg("^:$userhost NICK :", $dataregexp))
         {
            #On extrait le nouveau nick
            $newnick = split(":", $dataregexp);
            $newnick = $newnick[2];
            $this->onNick(trim($nick), trim($user), trim($host), trim($newnick)); 
         }
         
         //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
         
         //Traitement du KICK
         If (ereg("^:$userhost KICK #", $dataregexp))
         {
            #On extrait le canal et le kické
            $kick = split(" ", $dataregexp); 
            $channel = $kick[2];
            $nickkicked = $kick[3];
            $this->onKick(trim($nick), trim($user), trim($host), trim($channel), trim($nickkicked)); 
         }
         
         //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
         
         //Traitement du QUIT
         If (ereg("^:$userhost QUIT :", $dataregexp))
         {
            #On extrait la raison du QUIT
            preg_match("/^:$userhost QUIT :(.*?$)/", $dataregexp, $reason);
            $reason = $reason[1];
            $this->onQuit(trim($nick), trim($user), trim($host), trim($reason)); 
         }
         
         //**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
          
        }
    
        $this->Timer5Sec($dix, $quinze);
        
        //Réinitialisation de variables
        unset($server, $userhost, $message, $newnick, $reason, $nickkicked, $channel);
        
  
      }   
      
        ####$read = array($this->sirc);
        ####socket_select($read, $write = NULL, $except = NULL, 0); 
        ####if (count($read) > 0) {
        ####  $this->nbReadError++;
        ####  print "READ ERROR!!: ".socket_strerror(socket_last_error())."\n\n";
        #### if ($this->nbReadError > 2) {
        ####    $this->deconnexion("ARRRG! Je viens de rencontrer une erreur fatale :(.  Debug: ".socket_strerror(socket_last_error()));
        ####    break; 
        ####  }
        ####}   
        
    } 
  
  }


///////////////////////////////////////////////////////////////
	
  Function deconnexion($reason)
  /**
  * Destructeur; termine la connexion au serveur IRC
  *
  * @author Homer
  * @created 30 mai 2005
  * @modified 30 mai 2005
  * @param reason 	- Raison de la déconnexion
  * @return boolean - True si la déconnexion réussie, false sinon.
  */ 
	
  {
    $ok = $this->sendRaw("QUIT :$reason");
    If ($ok)
    {
      socket_close($this->sirc);
      $this->sirc = null;
      $this->exit = true;
      return true;
    } Else {
      return false;
    }
	
  }
  
///////////////////////////////////////////////////////////////

  Function privmsg($dest, $message)
  /**
  * Envoit un privmsg au destinataire
  *
  * @author Homer
  * @created 20 juin 2005
  * @modified 20 juin 2005
  * @param dest     - Destinataire (un pseudo ou un canal)
  * @param message  - Message à envoyer
  * @return none 
  */
  {
    $this->sendRaw("PRIVMSG $dest :$message");
  }
  
///////////////////////////////////////////////////////////////

  Function notice($dest, $message)
  /**
  * Envoit une notice au destinataire (ou un privmsg)
  *
  * @author Homer
  * @created 20 juin 2005
  * @modified 20 juin 2005
  * @param dest     - Destinataire (un pseudo ou un canal)
  * @param message  - Message à envoyer
  * @return none 
  */
  {
    global $db, $irpg;
    $uid = $irpg->getUIDByUsername($irpg->getUsernameByNick($dest));
    
    $tbUtil = $db->prefix."Utilisateurs";
    if ($db->nbLignes("SELECT Notice FROM $tbUtil WHERE Id_Utilisateurs='$uid' And Notice='N'") == 1)
    {
      $this->sendRaw("PRIVMSG $dest :$message");
    }
    else {
      $this->sendRaw("NOTICE $dest :$message");
    }
  }

///////////////////////////////////////////////////////////////

  Function isOn($channel, $nickname)
   /**
  * Vérifie la présence d'un utilisateur sur un canal
  *
  * @author Homer
  * @created 12 juillet 2005
  * @modified 12 juillet 2005
  * @param channel   - Canal à vérifier
  * @param nickname  - Pseudo à vérifier
  * @return boolean  - True si présent, False si absent 
  */
  {
    global $db;
    $table = $db->prefix."IRC";
    If ($db->nbLignes("SELECT Nick FROM $table WHERE Nick='$nickname' And Channel='$channel'") > 0)
    {
      return true;
    }
    Else {
      return false;
    }
    
  }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////	
	
}



?>
