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
* Module mod_objets
* Gestion des objets dans le jeu
*
* @author Homer
* @created 19 novembre 2005
*/

class objets
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
    $this->name = "mod_objets";
    $this->version = "0.9.0";
    $this->desc = "Module de gestion des objets";
    $this->depend = array("core/0.5.0", "idle/1.0.0");

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


    $message = trim(str_replace("\n", "", $message));
    $message = explode(" ", $message);
    $nb = count($message) - 1;

    switch (strtoupper($message[0])) {
      case "ITEMS":
        //Retourne de l'info sur les ITEMS d'un personnage
        if ($nb < 1) { $this->cmdItems($nick); }
        else { $this->cmdItems($nick, $message[1]); }
        break;
    }
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

  function modIdle_onLvlUp($nick, $uid, $pid, $level2, $next) {
    //Les objets sont distribués lors de la monté de niveau..

    global $db, $irc, $irpg;
    $tbLst = $db->prefix."ListeObjets";
    $tbObj = $db->prefix."Objets";
    $nomPerso = $irpg->getNomPersoByPID($pid);


    //Objets uniques
    $obj = $db->getRows("SELECT Id_ListeObjets, Name, Probabilite, Type, Niveau FROM $tbLst WHERE EstUnique='O' And Minimum <= '$level2'");
    $i = 0;
    while ($i != count($obj)) {
      $oid = $obj[$i]["Id_ListeObjets"];
      $name = $obj[$i]["Name"];
      $proba = $obj[$i]["Probabilite"];
      $type = $obj[$i]["Type"];
      $niveau = $obj[$i]["Niveau"];

      if (rand(1, $proba) == 1) {
        //Objet unique trouvé
        //On vérifie si on a pas déjà cet objet..
        if ($db->nbLignes("SELECT Id_Objets FROM $tbObj WHERE Pers_Id='$pid' And LObj_Id = '$oid'") != 0) {
          $i++;
          continue; //on a déjà l'objet
        }
        else {
          $db->req("INSERT INTO $tbObj (`Pers_Id`, `LObj_Id`, `Level`) VALUES ('$pid', '$oid', '$niveau')");
          $irc->notice($nick, "Félicitations!  Ton personnage \002$nomPerso\002 vient de trouver un objet unique : \002$name\002 de niveau \002$niveau\002 !");

          $req = "SELECT Id_Objets, Level FROM $tbObj WHERE Pers_Id='$pid' And LObj_Id IN (SELECT Id_ListeObjets FROM $tbLst WHERE Type='$type')";
          if ($db->nbLignes($req) == 1) {
            $res = $db->getRows($req);
            $oid2 = $res[0]["Id_Objets"];
            $olevel = $res[0]["Level"];
            if ($niveau > $olevel) {
              $db->req("UPDATE $tbObj SET Level='$niveau' WHERE Id_Objets='$oid2'");
            }
          }
          else {
            $db->req("INSERT INTO $tbObj (`Pers_Id`, `LObj_Id`, `Level`) VALUES (`$pid`, `$oid`, `$niveau`)");
          }

          $irpg->Log($pid, "OBJ_UNIQUE", 0, "$name (niveau $niveau)");
          return;
        }
      }
      $i++;
    }

    //Objets ordinaires
    $obj = $db->getRows("SELECT Id_ListeObjets, Name FROM $tbLst WHERE EstUnique='N' ORDER BY Reverse(Rand()) LIMIT 0,1");

    $oid = $obj[0]["Id_ListeObjets"];
    $nom = $obj[0]["Name"];

    //De quel niveau sera l'objet ?
    $lvlObj = 1;
    $i = 0;
    while ($i < round($level2*1.5, 0)) {
      if (rand(1, pow(1.4, $i/4)) == 1) {
        $lvlObj = $i;
      }
      $i++;
    }

    //On recherche si le personnage a déjà cet objet,
    //et si oui, on vérifie si le niveau est moins élevé
    $req = "SELECT Level, Id_Objets FROM $tbObj WHERE Pers_Id='$pid' And LObj_Id = '$oid'";
    if ($db->nbLignes($req) == 1) {
      $obj = $db->getRows($req);
      $niveau = $obj[0]["Level"];
      $oid = $obj[0]["Id_Objets"];

      if ($lvlObj > $niveau) {
        //Nouvel objet plus grand
        $db->req("UPDATE $tbObj SET Level='$lvlObj' WHERE Id_Objets='$oid'");
        $irc->notice($nick, "Ton personnage \002$nomPerso\002 vient de trouver l'objet \002$nom\002 de niveau \002$lvlObj\002.  Tu possédais déjà cet objet, mais avec un niveau $niveau, la chance est de ton côté!");
        $irpg->Log($pid, "OBJ", 0, "$nom (niveau $lvlObj)");
      }
      else {
        //Objet plus petit que ce qu'on a déjà
        $irc->notice($nick, "Ton personnage \002$nomPerso\002 vient de trouver l'objet \002$nom\002 de niveau \002$lvlObj\002.  Malheureusement, tu as déjà cet objet avec un niveau $niveau.");
      }

    }
    else {
      //Nouvel objet trouvé
      $db->req("INSERT INTO $tbObj (`Pers_Id`, `LObj_Id`, `Level`) VALUES ('$pid', '$oid', '$lvlObj')");
      $irc->notice($nick, "Ton personnage \002$nomPerso\002 vient de trouver un nouvel objet !  Il s'agit de l'objet \002$nom\002 de niveau \002$lvlObj\002.");
      $irpg->Log($pid, "OBJ", 0, "$nom (niveau $lvlObj)");
    }


  }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

  function cmdItems($nick, $perso = "") {
    global $irpg, $irc, $db;
    $uid = $irpg->getUsernameByNick($nick, true);
    $uid = $uid[1];

    if ($uid) {

      if (empty($perso)) {
        //On retourne les stats pour les personnages du joueur
        $tbPerso = $db->prefix."Personnages";
        $res = $db->getRows("SELECT Nom FROM $tbPerso WHERE Util_Id='$uid'");

        $i=0;
        while ($i != count($res)) {
          $perso = $res[$i]["Nom"];
          $this->envoyerInfoObjets($nick, $perso);
          $i++;
        }


      }
      else {
        if ($irpg->getPIDByPerso($perso)) {
          $this->envoyerInfoObjets($nick, $perso);
        }
        else {
          $irc->notice($nick, "Désolé, je ne connais pas $perso.");
        }
      }
    }
    else {
      $irc->notice($nick, "Désolé, vous devez être authentifié pour utiliser cette commande.");
    }

  }


  function infoObjets($pid, $detail = false) {
    //Calcul la somme des objets pour un personnage
    global $irpg, $db;

    $tbObj = $db->prefix."Objets";
    if ($detail) {
      $res = $db->getRows("SELECT LObj_Id, Level FROM $tbObj WHERE Pers_Id='$pid'");
    }
    else {
      $res = $db->getRows("SELECT Level FROM $tbObj WHERE Pers_Id='$pid'");
    }

    $i = 0;
    $sum = 0;
    while($i != count($res)) {
      if ($detail) {
        $oid = $res[$i]["LObj_Id"];
        $level = $res[$i]["Level"];

        $tbLst = $db->prefix."ListeObjets";
        $nomObj = $db->getRows("SELECT Name FROM $tbLst WHERE Id_ListeObjets='$oid'");
        $objets[] = array($nomObj[0]["Name"], $level);
      }
      $sum = $sum + $res[$i]["Level"];
      $i++;
    }

    if ($detail) {
      return array($sum, $objets);
    }
    else {
      return $sum;
    }
  }

  function envoyerInfoObjets($nick, $perso) {
    global $db, $irpg, $irc;;
    //On retourne les stats pour le personnage spécifié
    $pid = $irpg->getPIDByPerso($perso);
    $objets = $this->infoObjets($pid, true);
    $sum = $objets[0];
    $objets = $objets[1];

    $i = 0;
    $lstObj = "";

    if (count($objets) == 0) {
      $irc->notice($nick, "$perso n'a aucun objets.");
    }
    else {
      while($i != count($objets)) {
        if (empty($lstObj)) {
          $lstObj = "\002".$objets[$i][0]."\002: ".$objets[$i][1];
        }
        else {
          $lstObj = $lstObj.", \002".$objets[$i][0]."\002: ".$objets[$i][1];
        }
        $i++;
      }
      $irc->notice($nick, "Les objets de $perso sont: $lstObj.  La somme est de \002$sum\002.");
    }
  }

}
?>
