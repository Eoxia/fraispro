CREATE TABLE llx_fraispro_receipt_det
(
   rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
   fk_fraispro_receipt integer NOT NULL,
   fk_parent_line integer NULL,
   docnumber varchar(128),										-- To store a ref of a accounting doc (piece)
   fk_product integer NULL,
   ref varchar(128),  											-- supplier product ref
   label varchar(255),  										-- product label
   fk_c_type_fees integer,										-- Type of expense (from NDF)
   fk_c_exp_tax_cat integer,
   fk_projet integer,											-- Id of project
   comments text NOT NULL,
   description text,
   product_type integer DEFAULT -1,
   qty real NOT NULL,
   pu_ht double(24,8) DEFAULT 0, 								-- unit price excluding tax (Facture)
   pu_ttc double(24,8) DEFAULT 0, 								-- unit price with tax (Facture)
   subprice double(24,8) DEFAULT 0 NOT NULL, 					-- unit price HT (NDF)
   subprice_ttc double(24,8) DEFAULT 0,    	     				-- unit price if price was entered including tax (NDF)
   value_unit double(24,8) NOT NULL,          					-- P.U. TTC (NDF)
   remise_percent real DEFAULT 0,
   fk_remise_except integer NULL,
   vat_src_code varchar(10) DEFAULT '',
   tva_tx double(7,4),											-- Vat rate
   localtax1_tx double(7,4) DEFAULT 0,    						-- localtax1 rate
   localtax1_type varchar(10) NULL, 							-- localtax1 type
   localtax2_tx double(7,4) DEFAULT 0,    						-- localtax2 rate
   localtax2_type varchar(10) NULL, 							-- localtax2 type
   total_ht double(24,8) DEFAULT 0 NOT NULL,
   total_tva double(24,8) DEFAULT 0 NOT NULL,
   total_localtax1 double(24,8) DEFAULT 0,
   total_localtax2 double(24,8) DEFAULT 0,
   total_ttc double(24,8) DEFAULT 0 NOT NULL,
   date date,													-- From NDF
   date_start datetime DEFAULT NULL,       						-- date debut si service
   date_end datetime DEFAULT NULL,       						-- date fin si service
   info_bits integer DEFAULT 0,
   special_code integer DEFAULT 0,
   fk_unit integer DEFAULT NULL,
   fk_multicurrency integer,
   multicurrency_code varchar(3),
   multicurrency_subprice double(24,8) DEFAULT 0,
   multicurrency_subprice_ttc double(24,8) DEFAULT 0,
   multicurrency_total_ht double(24,8) DEFAULT 0,
   multicurrency_total_tva double(24,8) DEFAULT 0,
   multicurrency_total_ttc double(24,8) DEFAULT 0,
   fk_facture integer DEFAULT 0,
   fk_ecm_files integer DEFAULT NULL,
   fk_code_ventilation integer DEFAULT 0,
   rang integer DEFAULT 0,
   import_key varchar(14),
   rule_warning_message text,
   extraparams varchar(255)
) ENGINE=innodb;
