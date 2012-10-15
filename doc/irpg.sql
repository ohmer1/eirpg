-- ----------------------------------------------------------------------------------------
-- EpiKnet Idle RPG (EIRPG)
-- Copyright (C) 2005-2012 Francis D (Homer) & EpiKnet
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License version 3 as
-- published by the Free Software Foundation.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU Affero General Public License for more details.
--
-- You should have received a copy of the GNU Affero General Public License
-- along with this program. if not, see <http://www.gnu.org/licenses/>.
-- ----------------------------------------------------------------------------------------

--
-- Structure de la table `Equipes`
--

CREATE TABLE `Equipes` (
  `Id_Equipes` smallint(3) unsigned NOT NULL auto_increment,
  `Pers_Id` int(5) unsigned NOT NULL default '0',
  `Name` varchar(25) NOT NULL default '',
  `Description` varchar(100) NOT NULL default '',
  `Created` datetime NOT NULL default '0000-00-00 00:00:00',
  `Password` varchar(32) NOT NULL default '',
  `Valid` int(1) NOT NULL default '0',
  PRIMARY KEY  (`Id_Equipes`),
  KEY `Pers_Id` (`Pers_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Informations sur les équipes' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `IRC`
--

CREATE TABLE `IRC` (
  `Id_IRC` int(5) unsigned NOT NULL auto_increment,
  `Pers_Id` int(5) unsigned default NULL,
  `Nick` varchar(30) NOT NULL default '',
  `UserHost` varchar(100) default NULL,
  `Channel` varchar(30) NOT NULL default '',
  PRIMARY KEY  (`Id_IRC`),
  UNIQUE KEY `Pers_Id` (`Pers_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Informations IRC sur les joueurs';

-- --------------------------------------------------------

--
-- Structure de la table `ListeObjets`
--

CREATE TABLE `ListeObjets` (
  `Id_ListeObjets` tinyint(3) unsigned NOT NULL auto_increment,
  `Name` varchar(50) NOT NULL default '',
  `Minimum` tinyint(3) NOT NULL default '0',
  `EstUnique` enum('O','N') NOT NULL default 'N',
  `Probabilite` tinyint(3) unsigned NOT NULL default '0',
  `Type` tinyint(3) unsigned default NULL,
  `Niveau` tinyint(3) unsigned default NULL,
  PRIMARY KEY  (`Id_ListeObjets`),
  UNIQUE KEY `LObj_Id` (`Type`),
  KEY `Minimum` (`Minimum`,`EstUnique`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Listes des objets disponibles';

-- --------------------------------------------------------

--
-- Structure de la table `Logs`
--

CREATE TABLE `Logs` (
  `Id_Logs` int(10) unsigned NOT NULL auto_increment,
  `Pers_Id` int(5) unsigned default '0',
  `Date` datetime NOT NULL default '0000-00-00 00:00:00',
  `Type` varchar(20) NOT NULL default '',
  `Modificateur` int(4) NOT NULL default '0',
  `Desc1` varchar(255) NOT NULL default '',
  `Desc2` varchar(255) NOT NULL default '',
  `Desc3` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`Id_Logs`),
  KEY `Pers_Id` (`Pers_Id`,`Type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Logs divers (administratifs, objets, modificateurs..)';

-- --------------------------------------------------------

--
-- Structure de la table `Objets`
--

CREATE TABLE `Objets` (
  `Id_Objets` int(6) unsigned NOT NULL auto_increment,
  `Pers_Id` int(5) unsigned NOT NULL default '0',
  `LObj_Id` tinyint(3) unsigned NOT NULL default '0',
  `Level` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`Id_Objets`),
  KEY `Pers_Id` (`Pers_Id`),
  KEY `LObj_Id` (`LObj_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Informations sur les objets de chaque personnage';

-- --------------------------------------------------------

--
-- Structure de la table `Penalites`
--

CREATE TABLE `Penalites` (
  `Id_Penalites` int(5) unsigned NOT NULL auto_increment,
  `Pers_Id` int(5) unsigned NOT NULL default '0',
  `Text` int(11) unsigned NOT NULL default '0',
  `Nick` int(11) unsigned NOT NULL default '0',
  `Part` int(11) unsigned NOT NULL default '0',
  `Kick` int(11) unsigned NOT NULL default '0',
  `Quit` int(11) unsigned NOT NULL default '0',
  `Logout` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`Id_Penalites`),
  UNIQUE KEY `Pers_Id` (`Pers_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Informations sur les pénalités reçu par un personnage';

-- --------------------------------------------------------

--
-- Structure de la table `Personnages`
--

CREATE TABLE `Personnages` (
  `Id_Personnages` int(5) unsigned NOT NULL auto_increment,
  `Util_Id` int(5) unsigned NOT NULL default '0',
  `Nom` varchar(30) NOT NULL default '',
  `Class` varchar(50) NOT NULL default '',
  `Level` tinyint(3) unsigned NOT NULL default '0',
  `Idled` int(11) unsigned NOT NULL default '0',
  `Next` int(11) NOT NULL default '0',
  `LastLogin` datetime NOT NULL default '0000-00-00 00:00:00',
  `LastLogout` datetime NOT NULL default '0000-00-00 00:00:00',
  `Created` datetime NOT NULL default '0000-00-00 00:00:00',
  `ChallengeNext` int(11) unsigned NOT NULL default '0',
  `ChallengeTimes` smallint(4) unsigned NOT NULL default '0',
  `Equi_Id` smallint(3) unsigned default NULL,
  PRIMARY KEY  (`Id_Personnages`),
  UNIQUE KEY `Nom` (`Nom`),
  KEY `Util_Id` (`Util_Id`),
  KEY `Equi_Id` (`Equi_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Informations sur les personnages';

-- --------------------------------------------------------

--
-- Structure de la table `Utilisateurs`
--

CREATE TABLE `Utilisateurs` (
  `Id_Utilisateurs` int(5) unsigned NOT NULL auto_increment,
  `Username` varchar(30) NOT NULL default '',
  `Password` varchar(32) NOT NULL default '',
  `Email` varchar(50) NOT NULL default '',
  `LastLogin` datetime NOT NULL default '0000-00-00 00:00:00',
  `LastLogout` datetime NOT NULL default '0000-00-00 00:00:00',
  `Created` datetime NOT NULL default '0000-00-00 00:00:00',
  `Admin` tinyint(2) unsigned NOT NULL default '0',
  `Notice` enum('O','N') NOT NULL default 'O',
  `NoOp` enum('O','N') NOT NULL default 'N',
  `NoExpire` enum('O','N') NOT NULL default 'N',
  PRIMARY KEY  (`Id_Utilisateurs`),
  UNIQUE KEY `Username` (`Username`),
  KEY `Admin` (`Admin`,`Notice`),
  KEY `NoOp` (`NoOp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Informations sur les utilisateurs du jeu';

--
-- Structure de la table `Textes`
--

CREATE TABLE `Textes` (
  `Id` int(5) unsigned NOT NULL auto_increment,
  `Type` varchar(255) NOT NULL default '',
  `Valeur` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stocke différents textes (calamités, godsends..)';

--
-- Contraintes pour la table `Equipes`
--
ALTER TABLE `Equipes`
  ADD CONSTRAINT `Equipes_1` FOREIGN KEY (`Pers_Id`) REFERENCES `Personnages` (`Id_Personnages`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `IRC`
--
ALTER TABLE `IRC`
  ADD CONSTRAINT `IRC_1` FOREIGN KEY (`Pers_Id`) REFERENCES `Personnages` (`Id_Personnages`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `ListeObjets`
--
ALTER TABLE `ListeObjets`
  ADD CONSTRAINT `ListeObjets_ibfk_1` FOREIGN KEY (`Type`) REFERENCES `ListeObjets` (`Id_ListeObjets`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `Logs`
--
ALTER TABLE `Logs`
  ADD CONSTRAINT `Logs_1` FOREIGN KEY (`Pers_Id`) REFERENCES `Personnages` (`Id_Personnages`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `Objets`
--
ALTER TABLE `Objets`
  ADD CONSTRAINT `Objets_1` FOREIGN KEY (`LObj_Id`) REFERENCES `ListeObjets` (`Id_ListeObjets`),
  ADD CONSTRAINT `Objets_2` FOREIGN KEY (`Pers_Id`) REFERENCES `Personnages` (`Id_Personnages`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `Penalites`
--
ALTER TABLE `Penalites`
  ADD CONSTRAINT `Penalites_1` FOREIGN KEY (`Pers_Id`) REFERENCES `Personnages` (`Id_Personnages`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `Personnages`
--
ALTER TABLE `Personnages`
  ADD CONSTRAINT `Personnages_1` FOREIGN KEY (`Equi_Id`) REFERENCES `Equipes` (`Id_Equipes`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `Personnages_2` FOREIGN KEY (`Util_Id`) REFERENCES `Utilisateurs` (`Id_Utilisateurs`) ON DELETE CASCADE ON UPDATE CASCADE;
