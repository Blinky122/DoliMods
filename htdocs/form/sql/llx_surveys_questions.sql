-- ============================================================================
-- Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
-- ===========================================================================

CREATE TABLE llx_surveys_questions (
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  type_question     decimal(1,0) NOT NULL default '0',
  group_question    varchar(16)  NOT NULL default 'NONE',
  status            decimal(1,0) NOT NULL default '0',
  lib               varchar(255) NOT NULL default '',
  lib_rep1          varchar(100) NOT NULL default '',
  lib_rep2          varchar(100) default NULL,
  lib_rep3          varchar(100) default NULL,
  lib_rep4          varchar(100) default NULL
)ENGINE=innodb;
