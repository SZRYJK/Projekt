DROP DATABASE IF EXISTS  Testy;
CREATE DATABASE Testy;
USE Testy;
CREATE Table uzytkownicy(
	id int unsigned AUTO_INCREMENT PRIMARY KEY,
    login text not null UNIQUE,
    password text not null,
    nauczyciel boolean not null
);
CREATE TABLE wyniki(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    id_testu int unsigned not null,
	id_uzytkownik int unsigned not null,
    ocena float not null
);
CREATE TABLE test(
	id int unsigned AUTO_INCREMENT PRIMARY KEY,
    nazwa text not null,
    opis text,
    max_pkt int not null
);
CREATE TABLE pytania(
	id int unsigned AUTO_INCREMENT primary key,
    id_testu int unsigned not null,
    tresc text not null,
    pkt int not null,
    dobra_odp varchar(1) not null
);
CREATE TABLE odpowiedzi(
	id_odp int unsigned AUTO_INCREMENT PRIMARY KEY,
    id_pytania int unsigned not null,
 	tresc text not null,
    literka varchar(1) not null
);
CREATE Table zaznaczone(
   id int unsigned AUTO_INCREMENT primary key,
   zaznaczona varchar(1) not null,
   id_uzytkownik int unsigned not null,
   id_test int unsigned not null,
   id_pytania int unsigned not null
);
ALTER TABLE wyniki add CONSTRAINT fk_wyniki_test foreign KEY (id_testu) references test(id);
ALTER TABLE wyniki add CONSTRAINT fk_wyniki_uzytkownik foreign KEY (id_uzytkownik) references uzytkownicy(id);
ALTER TABLE pytania add CONSTRAINT fk_pytania_test foreign KEY (id_testu) references test(id);
ALTER TABLE zaznaczone add CONSTRAINT fk_zaznaczone_uzytkownik foreign KEY (id_uzytkownik) references uzytkownicy(id);
ALTER TABLE zaznaczone add CONSTRAINT fk_zaznaczone_test foreign KEY (id_test) references test(id);
ALTER TABLE zaznaczone add CONSTRAINT fk_zaznaczone_pytanie foreign KEY (id_pytania) references pytania(id);