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

class DB
/**
* Classe DB; classe de connexion et de requêtes
* vers la base de données mySQL
*
* @author Homer
* @created 30 mai 2005
* @modified 1er juin 2005
*/

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

///////////////////////////////////////////////////////////////

  Function connexion($host, $login, $pass, $base, $prefix)
  {
    global $irpg;

    $this->host = $host;
    $this->login = $login;
    $this->pass = $pass;
    $this->base = $base;
    $this->prefix = $prefix;

    $irpg->alog("Connexion au serveur de bases de données...", true);
    If (mysql_connect($this->host, $this->login, $this->pass))
    {
      If (mysql_select_db($this->base))
      {
        $this->connected = true;
        return true;
      }
      Else
      {
        return false;
      }
    }
    Else {
      return false;
    }

  }

///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////

  Function deconnexion()
  {
    $this->connected = false;
    mysql_close();
  }

///////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////

  Function req($query, $ignoredebug = false)
  {
    global $irpg, $irc;

   If ($this->connected)
   {
     If (($irpg->readConfig("IRPG", "debug")) And (!$ignoredebug)) { $irpg->alog("SQL: ".$query); }

     If (mysql_ping())
     {
       return mysql_query($query);
     }
     Else {
       $this->connected = false;
       $irpg->pause = true;
       $irc->privmsg($irc->home, "Attention, jeu automatiquement désactivé!!  Raison: perte de la connexion au serveur de bases de données.  Une nouvelle tentative se fera toutes les 15 secondes...");
     }
   }
   Else {

   }
  }

///////////////////////////////////////////////////////////////

  Function nbLignes($query)
  {
    global $irpg;

    If ($irpg->readConfig("IRPG", "debug")) { $irpg->alog("SQL: ".$query); }

    return mysql_num_rows($this->req($query, true));

  }

///////////////////////////////////////////////////////////////

  Function getRows($query)
  {
    global $irpg;

    If ($irpg->readConfig("IRPG", "debug")) { $irpg->alog("SQL: ".$query); }

    $r = $this->req($query, true);
    While ($li = mysql_fetch_array($r)) {
      $enregistrements[] = $li;
    }

    return $enregistrements;
  }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

}
