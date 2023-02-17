DROP TABLE IF EXISTS pwdReset CASCADE;
DROP TABLE IF EXISTS utentebase CASCADE;
DROP TABLE IF EXISTS utenteeditor CASCADE;
DROP TABLE IF EXISTS amministratore CASCADE;
DROP TABLE IF EXISTS commento CASCADE;
DROP TABLE IF EXISTS commentoapprovato CASCADE;
DROP TABLE IF EXISTS notizia CASCADE;
DROP TABLE IF EXISTS notiziaapprovata CASCADE;
DROP TABLE IF EXISTS categoria CASCADE;

CREATE TABLE pwdReset(
	pwdResetId SERIAL PRIMARY KEY,
	pwdResetEmail VARCHAR(100) NOT NULL,
	pwdResetSelector VARCHAR(255) NOT NULL,
	pwdResetToken VARCHAR(255) NOT NULL,
	pwdResetExpires VARCHAR(255) NOT NULL
);

CREATE TABLE utentebase (
	email VARCHAR(100) PRIMARY KEY,
	nome VARCHAR(60) NOT NULL,
	cognome VARCHAR(60) NOT NULL,
	username VARCHAR(100) NOT NULL,
	pswd VARCHAR(255) NOT NULL,
	imgProfile bytea
);

CREATE TABLE utenteeditor (
	email VARCHAR(100) PRIMARY KEY,
	nome VARCHAR(60) NOT NULL,
	cognome VARCHAR(60) NOT NULL,
	username VARCHAR(100) NOT NULL,
	pswd VARCHAR(255) NOT NULL,
	imgProfile bytea
);

CREATE TABLE amministratore (
	email VARCHAR(100) PRIMARY KEY,
	nome VARCHAR(60) NOT NULL,
	cognome VARCHAR(60) NOT NULL,
	username VARCHAR(100) NOT NULL,
	pswd VARCHAR(255) NOT NULL,
	imgProfile bytea
);

CREATE TABLE commento (
	codice SERIAL PRIMARY KEY,
	descrizione VARCHAR(500) NOT NULL,
	datainserimento TIMESTAMP NOT NULL,
	emailcom VARCHAR(100) NOT NULL,
	titolo VARCHAR(200) NOT NULL,
	stato BOOLEAN,
	UNIQUE(datainserimento, emailcom, titolo)
);

CREATE TABLE commentoapprovato (
	dataapprovazione TIMESTAMP NOT NULL,
	datainserimento TIMESTAMP,
	emailcom VARCHAR(100),
	titolo VARCHAR(200),
	PRIMARY KEY(datainserimento, emailcom, titolo)
);

CREATE TABLE notizia (
	datapubblicazione VARCHAR(100) NOT NULL,
	emailpub VARCHAR(100) NOT NULL,
	titolo VARCHAR(200) PRIMARY KEY,
	contenuto VARCHAR(5000) NOT NULL,
	numcommenti INTEGER NOT NULL,
	categoria VARCHAR(100) NOT NULL,
	immagine BYTEA NOT NULL, 
	stato BOOLEAN,
	audio BYTEA
);

CREATE TABLE notiziaapprovata (
	titolo VARCHAR(200) PRIMARY KEY,
	dataapprovazione TIMESTAMP NOT NULL
);

CREATE TABLE categoria (
	nome VARCHAR(100) PRIMARY KEY
);

GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO www;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO www;

INSERT INTO categoria VALUES('Intelligenza artificiale');
INSERT INTO categoria VALUES('Digital economy');
INSERT INTO categoria VALUES('Sicurezza');
INSERT INTO categoria VALUES('Digital life');
INSERT INTO categoria VALUES('Techno-products');
INSERT INTO categoria VALUES('Motors');

CREATE OR REPLACE FUNCTION approvaCommento() RETURNS TRIGGER AS $$
DECLARE 
	item RECORD;
BEGIN
	UPDATE commento SET stato = 'true' WHERE (datainserimento = NEW.datainserimento AND emailcom = NEW.emailcom AND titolo = NEW.titolo);
	UPDATE notizia SET numcommenti = numcommenti + 1 WHERE notizia.titolo = NEW.titolo;
RETURN NULL;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE TRIGGER updateStateCommento
AFTER INSERT ON commentoapprovato
FOR EACH ROW
EXECUTE PROCEDURE approvaCommento();

CREATE OR REPLACE FUNCTION approvaNotizia() RETURNS TRIGGER AS $$
BEGIN
	UPDATE notizia SET stato = 'true' WHERE (titolo = NEW.titolo);
RETURN NULL;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE TRIGGER updateStateNotizia
AFTER INSERT ON notiziaapprovata
FOR EACH ROW
EXECUTE PROCEDURE approvaNotizia();

CREATE OR REPLACE FUNCTION eliminacommentoapprovato() RETURNS TRIGGER AS $$
BEGIN
	DELETE FROM commentoapprovato WHERE titolo=OLD.titolo AND datainserimento=OLD.datainserimento AND emailcom=old.emailcom;
	UPDATE notizia SET numcommenti = numcommenti - 1 WHERE notizia.titolo = OLD.titolo;
RETURN NULL;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE TRIGGER eliminacommentoapprovato
AFTER DELETE ON commento
FOR EACH ROW
EXECUTE PROCEDURE eliminacommentoapprovato();

CREATE OR REPLACE FUNCTION eliminanotiziaapprovata() RETURNS TRIGGER AS $$
BEGIN
	DELETE FROM notiziaapprovata WHERE titolo = OLD.titolo;
	DELETE FROM commento WHERE titolo = OLD.titolo;
RETURN NULL;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE TRIGGER eliminanotiziaapprovata
AFTER DELETE ON notizia
FOR EACH ROW
EXECUTE PROCEDURE eliminanotiziaapprovata();