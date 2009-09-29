-- ===================================================================
-- Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id: llx_voyage_reduc.sql,v 1.1 2009/09/29 17:45:30 eldy Exp $
-- ===================================================================

create table llx_voyage_reduc
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,
  datec           datetime,
  datev           date,           -- date de valeur
  date_debut      date,           -- date operation
  date_fin        date,
  amount          real NOT NULL DEFAULT 0,
  label           varchar(255),
  numero          varchar(255),
  fk_type         smallint,       -- Train, Avion, Bateaux
  note            text
)type=innodb;
