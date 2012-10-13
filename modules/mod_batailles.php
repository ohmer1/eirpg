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

// TODO: batailles manuelles

/**
* Module mod_batailles.php
* Gestion des batailles dans le jeu
*
* @author Homer
* @created 13 mai 2006
*/
class batailles
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
    $this->name = "mod_batailles";
    $this->version = "0.5.0";
    $this->desc = "Module de gestion des batailles";
    $this->depend = array("core/0.5.0", "idle/1.0.0", "objets/0.9.0");

    //Recherche de dépendances
    if (!$irpg->checkDepd($this->depend)) {
      die("$this->name: dépendance non résolue\n");
    }

    //Validation du fichier de configuration spécifique au module
    $cfgKeys = array();
    $cfgKeysOpt = array();

    if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": Vérifiez votre fichier de configuration.\n");
    }

        //Initialisation des paramètres du fichier de configuration
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
/*
    switch (strtoupper($message[0])) {
      case "ITEMS":
        //Retourne de l'info sur les ITEMS d'un personnage
        if ($nb < 1) {
          $this->cmdItems($nick);
        } else {
          $this->cmdItems($nick, $message[1]);
        }
        break;
    }
*/
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

  function modIdle_onLvlUp($nick, $uid, $pid, $level, $next)
  {
    // À chaque monté de niveau,
    // .. il y a 25% de chance d'avoir une bataille lorsque niveau < 10
    // .. il y a 100% de chance d'avoir une bataille lorsque niveau >= 10
    if ($level >= 10) {
    	$this->batailleDuel($pid, $level);
    } else {
    	// 1 chance sur 4
      if (rand(1, 4) == 1) {
    		$this->batailleDuel($pid, $level);
	}
    }
  }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

  function batailleDuel($pid, $level)
  {
  	global $db, $irc, $irpg;

    $tIRC = $db->prefix . "IRC";
    $tPerso = $db->prefix . "Personnages";
    $perso = $irpg->getNomPersoByPID($pid);
    $uid = $irpg->getUIDByPID($pid);
    $level2 = $level + 1;

    $ttl = $db->getRows("SELECT Next FROM $tPerso WHERE Id_Personnages='$pid'");
    $ttl = $ttl[0]["Next"];

    // Sélectionne un autre joueur en ligne pour duel
    $q = "SELECT Pers_Id FROM $tIRC WHERE Pers_Id Not IN (SELECT Id_Personnages FROM $tPerso WHERE Util_Id='$uid') And Not IsNULL(Pers_Id) ORDER BY RAND() LIMIT 0,1";

    if ($db->nbLignes($q) == 0) {
    	return false;
    } else {
    	$res = $db->getRows($q);
      $pidOpp = $res[0]["Pers_Id"];
      $opposant = $irpg->getNomPersoByPID($pidOpp);

      $levelOpp = $db->getRows("SELECT Level FROM $tPerso WHERE Id_Personnages='$pidOpp'");
      $levelOpp = $levelOpp[0]["Level"];

      // Calcul des sommes
      $somme = $this->calcSomme($pid);
      $sommeOpp = $this->calcSomme($pidOpp);

      // Nombre aléatoire entre 0 et la somme
      $rand = rand(0, $somme);
      $randOpp = rand(0, $sommeOpp);

      if ($rand > $randOpp) {
      	//gagné..
        $mod = $levelOpp/4;

        if ($mod < 7) {
        	$mod = 7;
        }

        $mod = round(($mod/100) * $ttl, 0);
        $cmod = $irpg->convSecondes($mod);

        $db->req("UPDATE $tPerso SET Next=Next-$mod WHERE Id_Personnages='$pid'");
        $cnext = $db->getRows("SELECT Next FROM $tPerso WHERE Id_Personnages='$pid'");
        $cnext = $irpg->convSecondes($cnext[0]["Next"]);

        $irpg->Log($pid, "DUEL_AUTO", "GAGNÉ", "-$mod");

        $irc->privmsg($irc->home, "$perso [$rand/$somme] a provoqué en duel $opposant [$randOpp/$sommeOpp] et a gagné !  Cette victoire lui donne droit à un bonus de $cmod avant d'accéder au niveau $level2.  Prochain niveau dans $cnext.");

      } elseif ($rand < $randOpp) {
      	//perdu..
        $mod = $levelOpp/7;

        if ($mod < 7) {
          $mod = 7;
        }

        $mod = round(($mod/100) * $ttl, 0);
        $cmod = $irpg->convSecondes($mod);

        $db->req("UPDATE $tPerso SET Next=Next+$mod WHERE Id_Personnages='$pid'");
        $cnext = $db->getRows("SELECT Next FROM $tPerso WHERE Id_Personnages='$pid'");
        $cnext = $irpg->convSecondes($cnext[0]["Next"]);

        $irpg->Log($pid, "DUEL_AUTO", "PERDU", "$mod");

        $irc->privmsg($irc->home, "$perso [$rand/$somme] a provoqué en duel $opposant [$randOpp/$sommeOpp] et a perdu !  Cette défaite lui donne droit à une pénalité de $cmod avant d'accéder au niveau $level2.  Prochain niveau dans $cnext.");

      } else {
      	//match nul..
        $irpg->Log($pid, "DUEL_AUTO", "NUL", 0);
        $irc->privmsg($irc->home, "$perso [$rand/$somme] a provoqué en duel $opposant [$randOpp/$sommeOpp].  Match  nul !");

      }
    }
  }

/////////////////////////////////////////////////////////

  /*
  function BatailleManuelle($pid, $opposant = NULL )
  {
    global $db, $irc, $irpg;

    $tPerso = $db->prefix . "Personnages";
    $tIRC = $db->prefix."IRC";

    $uid = $irpg->getUIDByPID($pid);
    $perso = $db->getRows("SELECT * FROM $tPerso WHERE Id_Personnages='$pid'");
    $nom = $perso[0]["Nom"];
    $level = $perso[0]["Level"]+1;
    $next =  $perso[0]["Next"];
    $nbChallenges = $perso[0]["ChallengeTimes"];
    $ChallengeNext = $perso[0]["ChallengeNext"];

    $nick = $db->getRows("SELECT Nick FROM $tIRC WHERE Pers_Id='$pid'");
    $nick = $nick[0]["Nick"];

    if ( !$nbChallenges ) {
      //Premier combat du personnage
      $irc->notice($nick,"Bienvenue dans le module de combats manuels. bla bla bla");
      return false ;
      }

    if ($ChallengeNext) {
      //Temps avant challenge non terminé
      $cChallengeNext = $irpg->convSecondes($ChallengeNext);
      $irc->notice($nick,"Vous ne pouvez entreprendre de combats manuels en ce moment. Vous devez encore attendre $cChallengeNext avant d'initier un combat");
      return false ;
      }

    //Selection aléatoire d'un personnage à combattre s'il n'a pas été spécifié
    if ( !$opposant ) {
      $q = "SELECT Pers_Id FROM $tIRC WHERE Pers_Id Not IN (SELECT Id_Personnages FROM $tPerso WHERE Util_Id='$uid') And Not IsNULL(Pers_Id) ORDER BY RAND() LIMIT 0,1";
    } else {
      //Recherche du personnage spécifié
      if (!$db->nbLignes("SELECT Id_Personnages FROM $tPerso WHERE Nom='$opposant' And Not Util_Id='$uid' LIMIT 0,1")) {
        $irc->notice($nick,"Le personnage que vous désirez combattre n'existe pas");
        return false ;
        } else {
        $res = $db->getRows($q);
        $res = $res[0]["Id_Personnages"];
        $q = "SELECT Pers_Id FROM $tIRC WHERE Pers_Id = '$res'";
        }
      }

    //On verifie que la cible du combat est légale
    if (!$db->nbLignes($q)) {
      $irc->notice($nick,"Désolé, vous ne pouvez combattre actuellement, aucun personnage ne correspond aux critères requis");
      return false;
      } else {
      $res = $db->getRows($q);
      $pidOpp = $res[0]["Pers_Id"];
      $opposant = $irpg->getNomPersoByPID($pidOpp);
      }

    //////// A partir de ce point, le combat a lieu
    $nbChallenges++ ;

    $somme = $this->calcSomme($pid);
    $sommeOpp = $this->calcSomme($pidOpp);
    $rand = rand(0,$somme);
    $randOpp = rand(0,$sommeOpp);

    //Préparation du message qui sera affiché sur le canal
    $message = "$nom [$rand/$somme] a provoqué en duel $opposant [$randOpp/$sommeOpp]";

    if ( $rand > $randOpp ) {
      //Si victoire ($mod positif)
      if ($somme >= $sommeOpp) {
        $mod = (($sommeOpp/$somme)*$next)*0.15 ;
      } else {
        $mod = ((1 - $somme/$sommeOpp)*$next)*0.6 ;
      }

      $cmod = $irpg->convSecondes($mod);
      $message = $message . " et lui a fait mordre la poussière ! Cette victoire lui donne droit à un bonus de $cmod pour progresser vers le niveau $level.";

      if ( rand (1,35) == 1 ) {
        //Coup critique
        $nextOpp = $db->getRows("SELECT Next,Level FROM $tPerso WHERE pid='$pidOpp'");
        $levelOpp = $nextOpp[0]["Level"]+1 ;
        $nextOpp = $nextOpp[0]["Next"] ;
        $oppMod = (rand(5,25)/100)*$nextOpp ;
        $coppMod = $irpg->convSeccondes($oppMod) ;
        $db->req("UPDATE $tPerso SET Next=Next+$oppMod WHERE Id_Personnages='$pidOpp'");
        $cnextOpp = $nextOpp + $oppMod ;
        $cnextOpp = $irpg->convSecondes($cnextOpp);
        $message = $message . " COUP CRITIQUE !!!! $opposant reçoit un violent coup sur le crâne qui l'estourbi et le ralenti de $coppMod vers le niveau $levelOpp. Il atteindra ce niveau dans $cnextOpp." ;
        }
      } elseif ( $rand < $randOpp ) {
      //Si défaite ($mod négatif)
      if ($somme >= $sommeOpp) {
        $mod = -(($sommeOpp/$somme)*$next)*0.12 ;
      } else {
        $mod = -((1 - $somme/$sommeOpp)*$next)*0.5 ;
      }

      $cmod = $irpg->convSecondes(-$mod);
      $message = $message . " et s'est fait corriger ! Cette défaite lui ajoute une pénalite de $cmod pour progresser vers le niveau $level.";
      } else {
      //Match nul
      $mod = 0;
      $message = $message . ". Le combat s'est soldé sur un match nul. Les deux combattants se séparent sous la huée des spectateurs.";
      }

    //Mise à jour du temps avant prochain niveau et du nombre de victoires
    $db->req("UPDATE $tPerso SET Next=Next+$mod WHERE Id_Personnages='$pid'");
    $db->req("UPDATE $tPerso SET ChallengeTimes=$nbChallenges WHERE Id_Personnages='$pid'");

    $cnext = $irpg->convSecondes($next+$mod);
    $message = $message . " Prochain niveau dans $cnext." ;

    //Affichage du message crée
    $irc->privmsg($irc->home, $message);

    $ChallengeNext = ($nbChallenges+2)^4.3 ;
    $db->req("UPDATE $tPerso SET ChallengeNext=$ChallengeNext WHERE Id_Personnages='$pid'");
    $cChallengeNext = $irpg->convSecondes($ChallengeNext);
    //Message affichant le temps d'attente avant un nouveau challenge
    $irc->notice($nick, "Vous devrez attendre $cChallengeNext avant d'initier un nouveau combat");
    }
 */

  /////////////////////////////////////////////////////////

  function calcSomme($pid)
  {
  	// Calcul la somme des objets d'un joueur
    global $db;

    $t = $db->prefix . "Objets";
    $q = "SELECT Level FROM $t WHERE Pers_Id='$pid'";

    if ($db->nbLignes($q) > 0) {
      $res = $db->req($q);
      $somme = 0;
      while ($li = mysql_fetch_array($res)) {
    	 $somme = $somme + $li["Level"];
      }
    } else {
    	return 0;
    }

    return $somme;
  }

///////////////////////////////////////////////////////////////
}
?>
