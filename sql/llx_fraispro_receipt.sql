-- ============================================================================
CREATE TABLE llx_fraispro_receipt
(
   rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
   ref varchar(128) NOT NULL,
   entity integer DEFAULT 1 NOT NULL,
   fk_user_creat integer,
   fk_user_modif integer,
   date_creation datetime,
   tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   status integer DEFAULT 0,
   fk_expensereport integer,
   fk_facture_fourn integer,
   date_receipt date,
   total_ht double(24,8) DEFAULT 0 NOT NULL,
   total_tva double(24,8) DEFAULT 0 NOT NULL,
   total_ttc double(24,8) DEFAULT 0 NOT NULL,
   sha varchar(255),
   description text,
   fk_project integer
) ENGINE=innodb;
