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
* Classe DB; classe de connexion et de requêtes
* vers la base de données mySQL
*
* @author Homer
* @created 30 mai 2005
* @modified 1er juin 2005
*/
class DB
{
///////////////////////////////////////////////////////////////
// Variables privées
///////////////////////////////////////////////////////////////
  var $host;
  var $login;
  var $pass;
  var $base;
  var $prefix;
  var $connected; //Indique si nous sommes connectés à la bd SQL
///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////
// Méthodes privées, même si PHP s'en fou !
///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////
// Méthodes publiques
///////////////////////////////////////////////////////////////

  function connexion($host, $login, $pass, $base, $prefix)
  {
    global $irpg;

    $this->host = $host;
    $this->login = $login;
    $this->pass = $pass;
    $this->base = $base;
    $this->prefix = $prefix;

    $irpg->alog("Connexion au serveur de bases de données...", true);
    if (mysql_connect($this->host, $this->login, $this->pass)) {
      if (mysql_select_db($this->base)) {
        $this->connected = true;
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

///////////////////////////////////////////////////////////////

  function deconnexion()
  {
    $this->connected = false;
    mysql_close();
  }

///////////////////////////////////////////////////////////////

  function req($query, $ignoredebug = false)
  {
    global $irpg, $irc;

   if ($this->connected) {
     if (($irpg->readConfig("IRPG", "debug")) && (!$ignoredebug)) {
       $irpg->alog("SQL: ".$query);
     }

     if (mysql_ping()) {
       return mysql_query($query);
     } else {
       $this->connected = false;
       $irpg->pause = true;
       $irc->privmsg($irc->home, "Attention, jeu automatiquement désactivé!!  Raison: perte de la connexion au serveur de bases de données.  Une nouvelle tentative se fera toutes les 15 secondes...");
     }
   }
  }

///////////////////////////////////////////////////////////////

  function nbLignes($query)
  {
    global $irpg;

    if ($irpg->readConfig("IRPG", "debug")) {
      $irpg->alog("SQL: ".$query);
    }

    return mysql_num_rows($this->req($query, true));
  }

///////////////////////////////////////////////////////////////

  function getRows($query)
  {
    global $irpg;

    if ($irpg->readConfig("IRPG", "debug")) {
      $irpg->alog("SQL: ".$query);
    }

    $r = $this->req($query, true);
    while ($li = mysql_fetch_array($r)) {
      $enregistrements[] = $li;
    }

    return $enregistrements;
  }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////
}
?>
