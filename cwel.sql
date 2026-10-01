DROP DATABASE IF EXISTS Testy;
CREATE DATABASE Testy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Testy;

SET NAMES utf8mb4;

CREATE TABLE uzytkownicy(
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
    pkt int not null
);

CREATE TABLE odpowiedzi(
    id_odp int unsigned AUTO_INCREMENT PRIMARY KEY,
    id_pytania int unsigned not null,
    tresc text not null,
    literka varchar(1) not null,
    poprawna boolean not null DEFAULT 0
);

CREATE TABLE zaznaczone(
   id int unsigned AUTO_INCREMENT primary key,
   id_uzytkownik int unsigned not null,
   id_test int unsigned not null,
   id_pytania int unsigned not null,
   id_odp int unsigned not null
);

ALTER TABLE wyniki add CONSTRAINT fk_wyniki_test foreign KEY (id_testu) references test(id);
ALTER TABLE wyniki add CONSTRAINT fk_wyniki_uzytkownik foreign KEY (id_uzytkownik) references uzytkownicy(id);
ALTER TABLE pytania add CONSTRAINT fk_pytania_test foreign KEY (id_testu) references test(id);
ALTER TABLE odpowiedzi add CONSTRAINT fk_odpowiedzi_pytanie foreign KEY (id_pytania) references pytania(id) ON DELETE CASCADE;
ALTER TABLE zaznaczone add CONSTRAINT fk_zaznaczone_uzytkownik foreign KEY (id_uzytkownik) references uzytkownicy(id);
ALTER TABLE zaznaczone add CONSTRAINT fk_zaznaczone_test foreign KEY (id_test) references test(id);
ALTER TABLE zaznaczone add CONSTRAINT fk_zaznaczone_pytanie foreign KEY (id_pytania) references pytania(id);
ALTER TABLE zaznaczone add CONSTRAINT fk_zaznaczone_odp foreign KEY (id_odp) references odpowiedzi(id_odp);

INSERT INTO test (nazwa, opis, max_pkt) VALUES
('Test 1: Podstawy SQL', 'Test ze znajomości podstawowych zapytań SQL.', 10),
('Test 2: Bazy Danych - Teoria', 'Podstawowe pojęcia i projektowanie baz danych.', 10),
('Test 3: Zaawansowany SQL (Wielokrotny wybór)', 'Agregacje, złączenia i podzapytania.', 10);

INSERT INTO pytania (id_testu, tresc, pkt) VALUES
(3, 'Które złączenia należą do złączeń zewnętrznych (Outer Joins)?', 1),
(3, 'Które klauzule są poprawnymi elementami zapytania SQL?', 1);

INSERT INTO odpowiedzi (id_pytania, tresc, literka, poprawna) VALUES 
(1, 'INNER JOIN', 'A', 0),
(1, 'FULL OUTER JOIN', 'B', 1),
(1, 'LEFT JOIN', 'C', 1),
(1, 'RIGHT JOIN', 'D', 1);

INSERT INTO odpowiedzi (id_pytania, tresc, literka, poprawna) VALUES 
(2, 'SELECT', 'A', 1),
(2, 'WHERE', 'B', 1),
(2, 'GROUP BY', 'C', 1),
(2, 'CLICK', 'D', 0);