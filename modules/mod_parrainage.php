<?php

/*
EpiKnet Idle RPG (EIRPG)
Copyright (C) 2005-2007 Francis D (Homer) & EpiKnet

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License
version 3 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
* Module mod_parrainage
* Gères la fonctionnalité de parrainage sur le bot.
*
* @author Homer
* @created 18 avril 2010
*/

/*
 * Modification à apporter à la BD
 * ALTER TABLE  `Utilisateurs` ADD  `idParrain` INT( 5 ) NULL DEFAULT NULL ;
 *
 * et à irpg.conf :
 *
 * [mod_parrainage]
 * actif = "1"
 * lvlBonus = "40"
 * pctBonus = "5"
 *
*/

class parrainage
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes dépendants

  //Variables supplémentaires
  var $actif;     //Si la fonctionalité de parainage est active.
  var $lvlBonus;   //Le level requis par le joueur invité avant de donner le bonus au parrain.
  var $pctBonus;   //Le % du TTL retiré au parrain.


//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////
  Function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'éxécute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg, $db;

    /* Renseignement des variables importantes */
    $this->name = "mod_parrainage";
    $this->version = "0.1.0";
    $this->desc = "Module gérant les fonctionalités de parrainage.";
    $this->depend = Array("idle/1.0.0");

    //Recherche de dépendances
    If (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: dépendance non résolue\n");
    }

    //Validation du fichier de configuration spécifique au module
    $cfgKeys = Array("actif", "lvlBonus", "pctBonus");
    $cfgKeysOpt = Array();

    If (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": Vérifiez votre fichier de configuration.\n");
    }

    $this->actif = $irpg->readConfig($this->name,"actif");
    $this->lvlBonus = $irpg->readConfig($this->name, "lvlBonus");
    $this->pctBonus = $irpg->readConfig($this->name, "pctBonus");
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

    //Implantation des commandes de base
    $message = trim(str_replace("\n", "", $message));
    $message = explode(" ", $message);
    $nb = count($message) - 1;

    switch (strtoupper($message[0])) {
      case "REGISTER2":
        //Création d'un compte sur le bot à l'aide d'un parrain
        If ($nb == 4) { $this->cmdRegister2($nick, $message[1], $message[2], $message[3]); }
        Else { $irc->notice($nick, "Syntaxe incorrecte.  Syntaxe: REGISTER2 <utilisateur> <mot de passe> <courriel> <parrain>."); }
        break;


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
///////////////////////////////////////////////////////////////

/* Fonctions reliés aux commandes reçues par le bot */

  //TODO: il serait préférable de laisser mod_core créer le compte et de gérer le parrain
  // suite à la réception d'un signal qu'un nouveau compte a été créé.
  Function cmdRegister2($nick, $username, $password, $email, $parrain)
  {
    global $irc, $irpg, $db;
    /* cmdREGISTER2 : crée un compte dans la base de données */

    // on vérififie si le module est actif
    if ($this->actif!="1")
    {
      $irc->notice($nick, "Désolé, la fonctionalité de parrainage n'est pas en fonction.");
      return false;
    }

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

    // on vérifie que le parrain existe
    $table = $db->prefix."Personnages";
    $r = $db->req("SELECT Nom FROM $table WHERE Nom='$parrain'");
    If (mysql_num_rows($r) == 0)
    {
      $irc->notice($nick, "Votre parrain n'a pas été trouvé.  Vous devez utiliser son nom de personnage IRPG.");
      return false;
    }

    //Requête SQL maintenant :)
    $table = $db->prefix."Utilisateurs";
    $db->req("INSERT INTO $table (`Username`, `Password`, `Email`, `Created`, `pidParrain`) VALUES ('$username', '$password', '$email', NOW(), '$parrain')");
    $irc->notice($nick, "Votre compte \002$username\002 a été créé avec succès !");
    $irc->notice($nick, "Vous pouvez à présent vous authentifier à l'aide de la commande \002LOGIN\002 puis ensuite créer votre premier personnage à l'aide de la commande \002CREATE\002.");
    $irc->privmsg($irc->home, "Bienvenue à notre nouveau joueur $username invité par $parrain, connecté sous le pseudo $nick !");

  }


///////////////////////////////////////////////////////////////

  function modIdle_onLvlUp($nick, $uid, $pid, $level, $next)
  {
    $tbUtil = $db->prefix."Utilisateurs";
    $tbPerso = $db->prefix."Personnages";

    if ($level==$this->lvlBonus)
    {
	$pidParrain = $db->getRow("SELECT pidParrain FROM $tbUtil WHERE uid=$uid");
	If (mysql_num_rows($r) != 0)
        {
          $pidParrain = $pidParrain[0]["pidParrain"];
          if ($pidParrain!="")
          {
            // on donne le bonus au parrain
            $query = "SELECT Nom, Level, Next FROM $tbPerso WHERE Id_Personnages=$pidParrain";
            if ($db->nbLignes($query) != 1) return false; //parrain non trouvé
            $leParrain = $db->getRows($query);

            $persoParrain = $leParrain[0]['Nom'];
            $level = $leParrain[0]['Level'];
            $next = $leParrain[0]['Next'];

            $bonus = ($this->pctBonus/100)*$next;
            $bonus = round($bonus, 0):
            $cbonus = $irpg->convSecondes($cbonus);
            $nouveauNext = $next - $bonus;
            $cnouveauNext = $irpg->convSecondes($cnouveauNext);

            $db->req("UPDATE $tbPerso SET Next=Next-$bonus WHERE Id_Personnages=$pidParrain");

            $perso = getNomPersoByPID($pid);
            $irc->privmsg($irc->home, "$persoParrain, le parrain de $perso est récompensé par le retrait de 5% de son TTL.  Ce bonus l'accélère de $cbonus!  Prochain niveau dans $cnouveauNext.");

          }
        }
    }
  }

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


}

?>
