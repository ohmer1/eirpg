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
* Module mod_core
* Coeur du robot IRPG et module indispensable
* à son fonctionnement.
*
* * Méthodes inter-modules crées dans ce module:
* - modCore_onLogin($nick, $uid, $pid, $level, $next)
*
* @author Homer
* @created 23 juin 2005
*/

class core
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes dépendants

  //Variables supplémentaires
  var $users;       //Utilisateurs authentifiés associés à leurs nicks
  var $autologged;  //Utilisateurs auto-loggués lors du démarrage du bot
  var $expPenalite; //Valeur exponentiel pour calculer les pénalités
  var $penLogout;   //Valeur pour la pénalité de la commande LOGOUT
  var $motd;        //MOTD affiché lors d'un LOGIN
  var $timerPing;   //Timer envoi du ping
  var $loginAllowed; //Permet de bloquer la commande LOGIN

//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////
  Function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'éxécute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg, $db;

    /* Renseignement des variables importantes */
    $this->name = "mod_core";
    $this->version = "0.5.0";
    $this->desc = "Module de base EIRPG";
    $this->depend = Array();

    //Recherche de dépendances
    If (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: dépendance non résolue\n");
    }

    //Validation du fichier de configuration spécifique au module
    $cfgKeys = Array("maxPerso", "penLogout", "expPenalite");
    $cfgKeysOpt = Array("motd");

    If (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": Vérifiez votre fichier de configuration.\n");
    }

    //Initialisation de l'array $this->users et $this->autologged
    $this->users = Array(); //TODO: Si rehash, ne doit pas réinitialiser
    $this->autologged = Array();

    $this->maxPerso = $irpg->readConfig($this->name,"maxPerso");
    $this->penLogout = $irpg->readConfig($this->name,"penLogout");
    $this->expPenalite = $irpg->readConfig($this->name, "expPenalite");
    $this->motd = $irpg->readConfig($this->name, "motd");
    $this->timerPing = 0;
    $this->loginAllowed = false;
  }

///////////////////////////////////////////////////////////////
  Function unloadModule()
  {
    //Destructeur; décharge le module
    //S'éxécute lors du SHUTDOWN du bot ou d'un REHASH
    global $irc, $irpg, $db;

    $irc->deconnexion("SHUTDOWN: mod_core a été déchargé!");
    $db->deconnexion();


  }

///////////////////////////////////////////////////////////////

  Function onConnect() {
    global $irc, $irpg, $db;
    //Effacement de la table IRC lors de la connexion
    $table = $db->prefix."IRC";
    $channel = $irpg->readConfig("IRC", "channel");
    $db->req("DELETE FROM $table WHERE ISNULL(Pers_Id)");
  }

///////////////////////////////////////////////////////////////

  Function onPrivmsgCanal($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////


  Function onPrivmsgPrive($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

    //Implantation des commandes de base
    $message = trim(str_replace("\n", "", $message));
    $message = explode(" ", $message);
    $nb = count($message) - 1;

    switch (strtoupper($message[0])) {
      case "HELP":
      case "AIDE":
        //Aide sommaire sur les commandes
        If ($nb != 0) { $this->cmdHelp($nick, $message[1]); }
        Else { $this->cmdHelp($nick); }
        break;
      case "LOGIN":
        //Authentification au bot
        If ($nb == 2) { $this->cmdLogin($nick, $user, $host, $message[1], $message[2]); }
        ElseIf ($nb == 3) { $this->cmdLogin($nick, $user, $host, $message[1], $message[2], $message[3]); }
        Else {
          $irc->notice($nick, "Syntaxe incorrecte.  Syntaxe: LOGIN <utilisateur> <mot de passe> [personnage].");
          $irc->notice($nick, "Le paramètre personnage est optionnel.  Ce paramètre permet de s'authentifier sur un personnage précis.  Si ce paramètre n'est pas indiqué, vous serez authentifié sur tous vos personnages créés sous votre compte.");        }
        break;
      case "LOGOUT":
        //Désauthentification au bot
        If ($nb == 0) { $this->cmdLogout($nick); }
        ElseIf ($nb == 2) { $this->cmdLogout($nick, $user, $host, $message[1], $message[2]); }
        Else { $irc->notice($nick, "Syntaxe incorrecte.  Syntaxe: LOGOUT [utilisateur] [mot de passe]"); }
        break;
      case "REGISTER":
        //Création d'un compte sur le bot
        If ($nb == 3) { $this->cmdRegister($nick, $message[1], $message[2], $message[3]); }
        Else { $irc->notice($nick, "Syntaxe incorrecte.  Syntaxe: REGISTER <utilisateur> <mot de passe> <courriel>."); }
        break;
      case "CREATE":
        //Création d'un personnage sur le bot
        If ($nb >= 2)
        {
          $i = 2;
          While ($i != count($message)) {
            $classe = "$classe $message[$i]";
            $i++;
          }
          $this->cmdCreate($nick, $user, $host, $message[1], trim($classe));
        }
        Else { $irc->notice($nick, "Syntaxe incorrecte.  Syntaxe: CREATE <nom personnage> <classe>."); }
        break;
      case "NOTICE":
        //Préférence d'envoi en PRIVMSG ou NOTICE
        If ($nb == 1) { $this->cmdNotice($nick, $message[1]); }
        Else { $irc->notice($nick, "Syntaxe incorrecte.  Syntaxe: NOTICE <on/off>."); }
        break;
      case "SENDPASS":
        //Envoi d'un mot de pass perdu par courriel
        If ($nb == 2) { $this->cmdSendPass($nick, $message[1], $message[2]); }
        Else { $irc->notice($nick, "Syntaxe incorrecte.  Syntaxe: SENDPASS <utilisateur> <courriel>"); }
        break;
      case "WHOAMI":
        //Information sur le compte/personnages
        If ($nb == 0) { $this->cmdWhoAmI($nick); }
        Else { $irc->notice($nick, "Syntaxe incorrecte.  Syntaxe: WHOAMI"); }
        break;
	  case "INFOUSER":
          If ($nb < 1) {$irc->notice($nick, "Syntaxe: INFOUSER <user>"); }
	  break;

	  case "INFOPERSO":
          If ($nb < 1) {$irc->notice($nick, "Syntaxe: INFOPERSO <perso>"); }
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

    //Ajout de l'utilisateur dans la table IRC
    $table = $db->prefix."IRC";
    If ($nick != $irc->me)
    {
      $channel = strtoupper($channel);
      $db->req("INSERT INTO $table (`Nick`, `Channel`, `UserHost`) VALUES ('$nick', '$channel', '$user@$host')");
    }
  }

///////////////////////////////////////////////////////////////

  Function onPart($nick, $user, $host, $channel) {
    global $irc, $irpg, $db;


  }


///////////////////////////////////////////////////////////////

  Function onNick($nick, $user, $host, $newnick) {
    global $irc, $irpg, $db;

    If ($nick == $irc->me)
    {
      $irc->me = $newnick;
    }
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

    //Envoi d'un ping toutes les 60sec pour éviter que le bot
    //ne se déconnecte (par la protection anti-timeout) en cas d'inactivité
    $this->timerPing++;
    If ($this->timerPing >=4) {
      $irc->sendRaw("PING EIRPG".mktime());
      $this->timerPing = 0;
    }

    //On autorise le LOGIN
    $this->loginAllowed = true;

  }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

/* Fonctions reliés aux commandes reçues par le bot */

  Function cmdRegister($nick, $username, $password, $email)
  {
    global $irc, $irpg, $db;
    /* cmdREGISTER : crée un compte dans la base de données */

    //On vérifie si l'utilisateur est sur le canal
    If (!$irc->isOn($irc->home, $nick))
    {
      $irc->notice($nick, "Désolé, vous devez être sur \002$irc->home\002 pour vous enregistrer.");
      return false;
    }
    //Validation du nom d'utilisateur
    ElseIf (strlen($username) > 30) {
      $irc->notice($nick, "Désolé, votre nom d'utilisateur est trop long.  La limite autorisée est de \00230\002 caractères.");
      return false;
    }
    ElseIf (!eregi("^[a-z0-9_-]+$", $username)) {
      $irc->notice($nick, "Désolé, votre nom d'utilisateur contient des caractères interdits.  Seuls les caractères \002alphanumériques\002, le \002tiret\002 et la \002barre de soulignement\002 sont autorisés.");
      return false;
    }
    ElseIf (((strtoupper($username) == "IRPG")) or ((strtoupper($username) == "EIRPG"))) {
      $irc->notice($nick, "Désolé, ce nom d'utilisateur est réservé.");
      return false;
    }
    Else {
      //On vérifie que le nom n'existe pas déjà
      $table = $db->prefix."Utilisateurs";
      $r = $db->req("SELECT Username FROM $table WHERE Username='$username'");
      If (mysql_num_rows($r) != 0)
      {
        $irc->notice($nick, "Désolé, ce nom d'utilisateur existe déjà.  Veuillez en choisir un autre.");
        return false;
      }
    }

    //Encryption du mot de passe
    $password = md5($password);

    //Validation de l'adresse de courriel
    If (!$this->validerMail($email))
    {
      $irc->notice($nick, "Désolé, votre adresse de courriel n'est pas valide.");
      return false;
    }


    //Requête SQL maintenant :)
    $db->req("INSERT INTO $table (`Username`, `Password`, `Email`, `Created`) VALUES ('$username', '$password', '$email', NOW())");
    $irc->notice($nick, "Votre compte \002$username\002 a été créé avec succès !");
    $irc->notice($nick, "Vous pouvez à présent vous authentifier à l'aide de la commande \002LOGIN\002 puis ensuite créer votre premier personnage à l'aide de la commande \002CREATE\002.");
    $irc->privmsg($irc->home, "Bienvenue à notre nouveau joueur $username, connecté sous le pseudo $nick !");
  }


///////////////////////////////////////////////////////////////


  Function cmdLogin($nick, $user, $host, $username, $password, $perso = null)
  {
    global $irc, $irpg, $db;
    $tbUtil = $db->prefix."Utilisateurs";
    $tbPerso = $db->prefix."Personnages";
    $tbIRC = $db->prefix."IRC";

    If (!$this->loginAllowed) {
      //On vérifie si le login est autorisé
      $irc->notice($nick, "Désolé, la commande LOGIN est désactivée.  Veuillez recommencer dans quelques minutes.");
      return false;
    }
    ElseIf (!$irc->isOn($irc->home, $nick))
    { //On vérifie si l'utilisateur est sur le canal
      $irc->notice($nick, "Désolé, vous devez être sur \002$irc->home\002 pour vous authentifier.");
      return false;
    }
    ElseIf ($db->nbLignes("SELECT Username FROM $tbUtil WHERE Username='$username' And Password=MD5('$password')") != 1)
    {
      //Si mot de passe invalide
      $irc->notice($nick, "Désolé, votre nom d'utilisateur et/ou votre mot de passe est incorrect.");
      return false;
    }
    ElseIf (array_key_exists($username, $this->users))
    { //Le compte est déjà utilisé
      $irc->notice($nick, "Désolé, quelqu'un (probablement vous) utilise déjà ce compte.  Utilisez la commande \002LOGOUT <utilisateur> <mot de passe>\002 pour le déconnecter (avec pénalité équivalente à celle d'un QUIT).");
      return false;
    }
    ElseIf (in_array($nick, $this->users))
    {
      //L'utilisateur est déjà identifié sous un autre compte
      $irc->notice($nick, "Désolé, vous êtes déjà authentifié sous un autre compte.");
      return false;
    }
    ElseIf (is_null($perso))
    { //Pas de perso spécifié, si un ou des persos existent, on les logs tous, sinon
      //on demande de créer un perso

      If ($db->nbLignes("SELECT Nom FROM $tbPerso WHERE Util_Id = (SELECT Id_Utilisateurs FROM $tbUtil WHERE Username = '$username')") == 0)
      { //Si aucun perso
        $irc->notice($nick, "\002AUTHENTIFICATION RÉUSSIE!\002  Vous êtes maintenant authentifié sous le compte \002$username\002, mais sous aucun personnage.");
        $irc->notice($nick, "Pour commencer à jouer, créez votre premier personnage à l'aide de la commande \002CREATE\002.");
        $irc->privmsg($irc->home, "$nick s'est connecté au compte $username.");

        $uid = $irpg->getUIDByUsername($username);

        //Appel aux autres modules
        $y = 0;
        While ($y != count($irpg->mod))
        {
          If (method_exists($irpg->mod[$irpg->modules[$y]], "modCore_onLogin"))
          {
            $irpg->mod[$irpg->modules[$y]]->modCore_onLogin($nick, $uid, NULL, NULL, NULL);
          }
          $y++;
        }
      }
      Else
      { //Si au moins un perso
        $persodb = $db->getRows("SELECT Nom, Util_Id, Class, Id_Personnages, Level, Next FROM $tbPerso WHERE Util_Id = (SELECT Id_Utilisateurs FROM $tbUtil WHERE Username = '$username')");
        $i = 0;
        While ($i != count($persodb))
        {
          $uid = $persodb[$i]["Util_Id"];
          $nomPerso = $persodb[$i]["Nom"];
          $classe = $persodb[$i]["Class"];
          $pid = $persodb[$i]["Id_Personnages"];
          $level = $persodb[$i]["Level"];
          $next = $persodb[$i]["Next"];

          $db->req("INSERT INTO $tbIRC (`Pers_Id`, `Nick`, `UserHost`, `Channel`) VALUES ('$pid', '$nick', '$user@$host', '$irc->home')");

          If (empty($lstPerso))
          {
            $lstPerso = "\002$nomPerso\002";
          }
          Else {
            $lstPerso = "$lstPerso, \002$nomPerso\002";
          }

          $cnext = $irpg->convSecondes($next);
          $irc->privmsg($irc->home, "$nomPerso ($username), $classe de niveau $level est maintenant connecté sous le pseudo $nick.  Prochain niveau dans $cnext.");

          //Update du lastlogin du personnage
          $db->req("UPDATE $tbPerso SET LastLogin = NOW() WHERE Id_Personnages = '$pid'");

          //Appel aux autres modules
          $y = 0;
          While ($y != count($irpg->mod))
          {
            If (method_exists($irpg->mod[$irpg->modules[$y]], "modCore_onLogin"))
            {
              $irpg->mod[$irpg->modules[$y]]->modCore_onLogin($nick, $uid, $pid, $level, $next);
            }
            $y++;
          }

          $i++;
        }

        $irc->notice($nick, "\002AUTHENTIFICATION RÉUSSIE!\002  Vous êtes maintenant authentifié sous le compte \002$username\002.");

        If ($i == 1)
        {
          $irc->notice($nick, "Vous jouez actuellement avec le personnage $lstPerso.");
        }
        Else {
          $irc->notice($nick, "Vous jouez actuellement avec les personnages suivants : $lstPerso.");
        }

        if (!empty($this->motd)) {
          $irc->notice($nick, "\002MOTD\002 -- ".$this->motd);
        }

        //À déplacer dans un module éventuellement..
        $tbMod = $db->prefix."Modules";
        $pub = $db->getRows("SELECT Valeur FROM $tbMod WHERE Module='pub' and Parametre='texte' ORDER BY RAND() LIMIT 0,1");
				$pub = $pub[0]["Valeur"];

        $irc->notice($nick, "\002Publicité\002 -- ".$pub);




      }


      //Dans les 2 cas..
      $this->users["$username"] = $nick;

      //On update aussi le lastlogin du compte
      $db->req("UPDATE $tbUtil SET LastLogin = NOW() WHERE Id_Utilisateurs = '$uid'");


    }
    else {
      //Login à un personnage spécifique
        $irc->notice($nick, "Fonction non développée actuellement.");
    }
  }

///////////////////////////////////////////////////////////////

  Function cmdLogout($nick, $user = null, $host = null, $username = null, $password = null)
  {
    global $irpg, $db, $irc;
    If (isset($username))
    {
      //Logout "distant"..
      $irc->notice($nick, "Désolé, cette fonction n'est pas encore développée.");


    }
    Else {
      //Logout du compte utilisé actuellement
      $tbIRC = $db->prefix."IRC";
      $tbPerso = $db->prefix."Personnages";
      $username = $irpg->getUsernameByNick($nick);

      If ($username)
      {
        #$irc->notice($nick, "Vous n'êtes plus authentifié.  Une pénalité P20 a été appliquée à vos personnages en ligne.");

        //On enlève l'utilisateur du tableau des utilisateurs en ligne'
        unset($this->users["$username"]);

        //On selectionne les noms de personnages et le next de chaque perso
        $res = $db->getRows("SELECT Nom, Next, Level, Id_Personnages FROM $tbPerso WHERE Id_Personnages IN (SELECT Pers_Id FROM $tbIRC WHERE Nick='$nick' And NOT ISNULL(Pers_Id))");
        $i = 0;
        While ($i != count($res))
        {
          //On applique les penalites
          $nom = $res[$i]["Nom"];
          $level = $res[$i]["Level"];
          $valeur = $this->penLogout;
          $expo = $this->expPenalite;
          $penalite = $valeur * pow($expo,$level);

          If ($penalite > 0) {
            $cpenalite = $irpg->convSecondes($penalite);
            $pid = $res[$i]["Id_Personnages"];
            $perso = $irpg->getNomPersoByPID($pid);
            $db->req("UPDATE $tbPerso SET Next=Next+$penalite WHERE Id_Personnages='$pid'");
            $next = $db->getRows("SELECT Next FROM $tbPerso WHERE Id_Personnages='$pid'");
            $next = $next[0]["Next"];
            $cnext = $irpg->convSecondes($res[$i]["Next"]);
            $irpg->Log($pid, "PENAL_LOGOUT", "$penalite", "$next");
            $irc->notice($nick, "Personnage $nom délogué avec une pénalité de $cpenalite.  Prochain niveau dans $cnext.");
          }

          $i++;
        }

        //On enlève les personnages en ligne du joueur
        $db->req("DELETE FROM $tbIRC WHERE Nick='$nick' And NOT ISNULL(Pers_Id)");

        //On lui retire ses modes..
        $irc->sendRaw("MODE $irc->home -ohv $nick $nick $nick");


      }
      Else {
        $irc->notice($nick, "Impossible de vous déloguer, car vous n'êtes actuellement pas authentifié.");
      }
    }
  }

///////////////////////////////////////////////////////////////

  Function cmdCreate($nick, $user, $host, $personnage, $classe) {
    global $irpg, $irc, $db;

    $tbPerso = $db->prefix."Personnages";
    $tbIRC = $db->prefix."IRC";
    $username = $irpg->getUsernameByNick($nick);
    $uid = $irpg->getUIDByUsername($username);

    If (!$username)
    {
      $irc->notice($nick, "Vous devez être authentifié pour utiliser cette commande.");
    }
    Else {
      //On vérifie si le nombre maximal de personnage n'a pas été atteint
      if ($db->nbLignes("SELECT Nom FROM $tbPerso WHERE Util_Id='$uid'") >= $this->maxPerso) {
        $irc->notice($nick, "Désolé, vous ne pouvez pas créer plus de $this->maxPerso personnage(s).");
      }
      //On vérifie la validité du nom de personnage
      elseif (strlen($personnage) > 30) {
        $irc->notice($nick, "Le nom de votre personnage est limité à 30 caractères.");
      }
      elseif (((strtoupper($personnage) == "IRPG")) or ((strtoupper($personnage) == "EIRPG"))) {
        $irc->notice($nick, "Désolé, ce nom de personnage est réservé.");
      }
      elseif ($db->nbLignes("SELECT Nom FROM $tbPerso WHERE Nom = '$personnage'") != 0) {
        $irc->notice($nick, "Désolé, ce nom de personnage est déjà en utilisation.");
      }
      //Puis la validité de la classe
      elseif (strlen($classe) > 50) {
        $irc->notice($nick, "La taille de votre classe ne peut dépasser 50 caractères.");
      }
      else {
        //Création du personnage
        $base = $irpg->mod["idle"]->idleBase;
        $db->req("INSERT INTO $tbPerso (`Util_Id`, `Nom`, `Class`, `LastLogin`, `Created`, `Next`) VALUES ('$uid', '$personnage', '$classe', NOW(), NOW(), '$base')");
        $pid = mysql_insert_id();
        $db->req("INSERT INTO $tbIRC (`Pers_Id`, `Nick`, `UserHost`, `Channel`) VALUES ('$pid', '$nick', '$user@$host', '$irc->home')");
        $irc->notice($nick, "Votre personnage \002$personnage\002 a été créé avec succès.  Vous avez été authentifié automatiquement avec ce personnage.");
        $irc->notice($nick, "Pour atteindre le niveau 1, vous devez idler pendant ".$irpg->convSecondes($base).".");
        $irc->privmsg($irc->home, "Bienvenue à $personnage, ".stripslashes($classe)." appartenant à $username/$nick. Premier niveau dans ".$irpg->convSecondes($base).".");
      }
    }

  }

///////////////////////////////////////////////////////////////

  Function cmdHelp($nick, $message = "") {
    global $irc;
    $irc->notice($nick, "Aide non disponible.");

  }

///////////////////////////////////////////////////////////////

  Function cmdSendPass($nick, $user, $pass) {
    global $irc;
    $irc->notice($nick, "Commande non disponible.");

  }

///////////////////////////////////////////////////////////////

  Function cmdNotice($nick, $flag) {
    global $irc, $db, $irpg;
    if ((strtolower($flag) != "on") and (strtolower($flag) != "off")) {
      $irc->notice($nick, "Syntaxe incorrecte.  Syntaxe: NOTICE <on/off>.");
    }
    else {
      $user = $irpg->getUsernameByNick($nick);
      $tbUtil = $db->prefix."Utilisateurs";
      if (strtolower($flag) == "on") {
        //On rétablis le message par notice
        $db->req("UPDATE $tbUtil SET Notice='O' WHERE Username='$user'");
        $irc->notice($nick, "Vous recevrez maintenant vos messages par notices.");
      }
      else {
        //On active les messages par privmsg
        $db->req("UPDATE $tbUtil SET Notice='N' WHERE Username='$user'");
        $irc->notice($nick, "Vous recevrez maintenant vos messages par privmsg.");
      }
    }

  }

///////////////////////////////////////////////////////////////

  Function cmdWhoAmI($nick) {
    global $irpg,$irc,$db;

    $user = $irpg->getUsernameByNick($nick, true);
    $username = $user[0];
    $uid = $user[1];

    If ($uid) {
      $irc->notice($nick, "Vous êtes actuellement connecté sous le compte \002$username\002.");

      $tbPerso = $db->prefix."Personnages";
      $res = $db->getRows("SELECT Nom, Class, Level, Next, Equi_Id FROM $tbPerso WHERE Util_Id='$uid'");

      $i=0;
      While ($i != count($res)) {
        $nom = $res[$i]["Nom"];
        $class = $res[$i]["Class"];
        $level = $res[$i]["Level"];
        $next = $irpg->convSecondes($res[$i]["Next"]);
        $equipe = $res[$i]["Equi_Id"];

        if (is_null($equipe)) {
          $irc->notice($nick, "Personnage \002$nom\002, $class de niveau $level.  Prochain niveau dans $next.");
        }
        else {
          $irc->notice($nick, "Personnage \002$nom\002, $class de niveau $level (membre de l'équipe \002$equipe\002).  Prochain niveau dans $next.");
        }

        $i++;
      }

    }
    Else {
      $irc->notice($nick, "Vous n'êtes pas authentifié actuellement.");
    }
  }


///////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////

/* Fonctions diverses */

  Function validerMail($mail)
  {

  return ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'.
               '@'.
               '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.
               '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$',
               $mail);

  }


////////////////////////////////////////////////////////////////

  Function resetAutoLogin()
  {
    $i = 0;
    $nb = count($this->autologged); //-1 à chaque loop, donc il faut la valeur inititialle
    While ($i != $nb)
    {
      unset($this->autologged[$i]);
      $i++;
    }
  }



///////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////


}

?>
