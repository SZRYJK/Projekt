DROP DATABASE IF EXISTS Testy;
CREATE DATABASE Testy CHARACTER SET utf8mb4 COLLATE utf8mb4_polish_ci;
USE Testy;

CREATE TABLE uzytkownicy(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    login varchar(50) not null UNIQUE,
    password varchar(255) not null,
    nauczyciel boolean not null
);

CREATE TABLE test(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    nazwa text not null,
    opis text,
    max_pkt int not null
);

CREATE TABLE wyniki(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    id_testu int unsigned not null,
    id_uzytkownik int unsigned not null,
    ocena float not null,
    CONSTRAINT fk_wyniki_test FOREIGN KEY (id_testu) REFERENCES test(id),
    CONSTRAINT fk_wyniki_uzytkownik FOREIGN KEY (id_uzytkownik) REFERENCES uzytkownicy(id)
);

CREATE TABLE pytania(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    id_testu int unsigned not null,
    tresc text not null,
    pkt int not null,
    dobra_odp varchar(1) not null,
    CONSTRAINT fk_pytania_test FOREIGN KEY (id_testu) REFERENCES test(id)
);

CREATE TABLE odpowiedzi(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    id_pytania int unsigned not null,
    tresc text not null,
    literka varchar(1) not null,
    CONSTRAINT fk_odpowiedzi_pytanie FOREIGN KEY (id_pytania) REFERENCES pytania(id)
);

CREATE TABLE zaznaczone(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    zaznaczona varchar(1) not null,
    id_uzytkownik int unsigned not null,
    id_test int unsigned not null,
    id_pytania int unsigned not null,
    CONSTRAINT fk_zaznaczone_uzytkownik FOREIGN KEY (id_uzytkownik) REFERENCES uzytkownicy(id),
    CONSTRAINT fk_zaznaczone_test FOREIGN KEY (id_test) REFERENCES test(id),
    CONSTRAINT fk_zaznaczone_pytanie FOREIGN KEY (id_pytania) REFERENCES pytania(id)
);

-- --- DANE TESTOWE (INSERTS) ---

-- Dodanie użytkowników (nauczyciel i uczeń)
INSERT INTO uzytkownicy (login, password, nauczyciel) VALUES 
('profesor', 'tajnehaslo1', 1),
('jan_kowalski', 'haslocusia123', 0);

-- Dodanie testu
INSERT INTO test (nazwa, opis, max_pkt) VALUES 
('Matematyka - Podstawy', 'Test z podstawowych działań matematycznych dla klasy 1.', 10);

-- Dodanie pytań do testu
INSERT INTO pytania (id_testu, tresc, pkt, dobra_odp) VALUES 
(1, 'Ile wynosi wynik działania 2 + 2 * 2?', 5, 'B'),
(1, 'Jaki jest pierwiastek kwadratowy z liczby 16?', 5, 'C');

-- Dodanie odpowiedzi do pytań
-- Dla pytania 1:
INSERT INTO odpowiedzi (id_pytania, tresc, literka) VALUES 
(1, '6', 'A'),
(1, '6 (błąd, miało być 6)', 'B'), -- Poprawna to 6, ale zrobmy ładniejsze warianty: 8, 6, 4
(1, '8', 'C');
-- Poprawmy te odpowiedzi dla pytania 1, żeby miały sens (A: 6, B: 8, C: 4 - poprawka dobra_odp na 'C'):
-- Przypiszmy prawidłowe warianty:
UPDATE pytania SET dobra_odp = 'C' WHERE id = 1;
UPDATE odpowiedzi SET tresc = '6' WHERE id_pytania = 1 AND literka = 'A';
UPDATE odpowiedzi SET tresc = '8' WHERE id_pytania = 1 AND literka = 'B';
-- Dodajmy brakującą literkę C dla pierwszego pytania:
INSERT INTO odpowiedzi (id_pytania, tresc, literka) VALUES (1, '4', 'C');

-- Odpowiedzi dla pytania 2:
INSERT INTO odpowiedzi (id_pytania, tresc, literka) VALUES 
(2, '2', 'A'),
(2, '8', 'B'),
(2, '4', 'C');

-- Dodanie wyniku ucznia
INSERT INTO wyniki (id_testu, id_uzytkownik, ocena) VALUES 
(1, 2, 5.0);

-- Dodanie zaznaczonych odpowiedzi przez ucznia
INSERT INTO zaznaczone (zaznaczona, id_uzytkownik, id_test, id_pytania) VALUES 
('C', 2, 1, 1),
('C', 2, 1, 2);