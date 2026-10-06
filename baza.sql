DROP DATABASE IF EXISTS Testy;
CREATE DATABASE Testy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Testy;

SET NAMES utf8mb4;

CREATE TABLE uzytkownicy(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    login varchar(191) not null UNIQUE,
    password text not null,
    nauczyciel boolean not null DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE test(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    nazwa text not null,
    opis text,
    max_pkt int not null DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pytania(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    id_testu int unsigned not null,
    tresc text not null,
    pkt int not null DEFAULT 1,
    dobra_odp varchar(1) not null DEFAULT 'A',
    CONSTRAINT fk_pytania_test FOREIGN KEY (id_testu) REFERENCES test(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE odpowiedzi(
    id_odp int unsigned AUTO_INCREMENT PRIMARY KEY,
    id_pytania int unsigned not null,
    tresc text not null,
    literka varchar(1) not null,
    poprawna boolean not null DEFAULT 0,
    CONSTRAINT fk_odpowiedzi_pytanie FOREIGN KEY (id_pytania) REFERENCES pytania(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wyniki(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    id_testu int unsigned not null,
    id_uzytkownik int unsigned not null,
    ocena float not null,
    data_wykonania timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wyniki_test FOREIGN KEY (id_testu) REFERENCES test(id) ON DELETE CASCADE,
    CONSTRAINT fk_wyniki_uzytkownik FOREIGN KEY (id_uzytkownik) REFERENCES uzytkownicy(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE zaznaczone(
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    zaznaczona varchar(1) not null,
    id_uzytkownik int unsigned not null,
    id_test int unsigned not null,
    id_pytania int unsigned not null,
    id_odp int unsigned null,
    CONSTRAINT fk_zaznaczone_uzytkownik FOREIGN KEY (id_uzytkownik) REFERENCES uzytkownicy(id) ON DELETE CASCADE,
    CONSTRAINT fk_zaznaczone_test FOREIGN KEY (id_test) REFERENCES test(id) ON DELETE CASCADE,
    CONSTRAINT fk_zaznaczone_pytanie FOREIGN KEY (id_pytania) REFERENCES pytania(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO uzytkownicy (id, login, password, nauczyciel) VALUES
(1, 'Ryszard', '12344321', 1),
(2, 'Klaudia', 'zaq1@WSX', 0),
(3, 'Aleks', '12345678', 0);

INSERT INTO test (id, nazwa, opis, max_pkt) VALUES
(1, 'Test 1: Podstawy SQL', 'Test ze znajomości podstawowych zapytań SQL oraz filtrowania i agregacji danych.', 10),
(2, 'Test 2: Bazy Danych - Teoria', 'Podstawowe pojęcia, postacie normalne, klucze i projektowanie relacyjnych baz danych.', 10),
(3, 'Test 3: Zaawansowany SQL', 'Agregacje, złączenia (JOIN), podzapytania oraz zaawansowane operacje w SQL.', 10);

INSERT INTO pytania (id, id_testu, tresc, pkt, dobra_odp) VALUES
(1, 1, 'Która instrukcja służy do pobierania danych?', 1, 'A'),
(2, 1, 'Która klauzula służy do filtrowania wyników?', 1, 'B'),
(3, 1, 'Które słowo kluczowe usuwa duplikaty z wyników?', 1, 'C'),
(4, 1, 'Która instrukcja służy do dodawania nowych wierszy?', 1, 'A'),
(5, 1, 'Jakiej instrukcji używamy do modyfikacji istniejących danych?', 1, 'B'),
(6, 1, 'Która klauzula pozwala na sortowanie wyników?', 1, 'C'),
(7, 1, 'Która funkcja agregująca zwraca liczbę wierszy?', 1, 'A'),
(8, 1, 'Które słowo kluczowe ogranicza liczbę zwracanych wierszy w MySQL?', 1, 'B'),
(9, 1, 'Jaki operator służy do sprawdzania dopasowania do wzorca (np. tekst)?', 1, 'C'),
(10, 1, 'Która instrukcja służy do usuwania tabeli z bazy?', 1, 'A'),

(11, 2, 'Co oznacza skrót DBMS?', 1, 'A'),
(12, 2, 'Która postać normalna eliminuje redundancję powtarzalnych grup?', 1, 'B'),
(13, 2, 'Co to jest klucz główny (PRIMARY KEY)?', 1, 'C'),
(14, 2, 'Do czego służy klucz obcy (FOREIGN KEY)?', 1, 'A'),
(15, 2, 'Co to jest transakcja w bazie danych?', 1, 'B'),
(16, 2, 'Która własność ACID oznacza niezależność transakcji?', 1, 'C'),
(17, 2, 'Jaki model bazy danych jest najpopularniejszy współcześnie?', 1, 'A'),
(18, 2, 'Czym jest indeks w bazie danych?', 1, 'B'),
(19, 2, 'Który język służy do definiowania struktury danych (DDL)?', 1, 'C'),
(20, 2, 'Który język służy do manipulacji danymi (DML)?', 1, 'A'),

(21, 3, 'Które złączenie zwraca wszystkie rekordy z obu tabel?', 1, 'B'),
(22, 3, 'Która klauzula służy do grupowania wyników?', 1, 'C'),
(23, 3, 'Czym różni się WHERE od HAVING?', 1, 'A'),
(24, 3, 'Jak nazywa się zapytanie zagnieżdżone wewnątrz innego zapytania?', 1, 'B'),
(25, 3, 'Który operator łączy wyniki dwóch zapytań usuwając duplikaty?', 1, 'C'),
(26, 3, 'Która funkcja zwraca wartość maksymalną?', 1, 'A'),
(27, 3, 'Które złączenie zwraca tylko pasujące rekordy z obu tabel?', 1, 'B'),
(28, 3, 'Do czego służy alias (AS)?', 1, 'C'),
(29, 3, 'Jaki operator służy do sprawdzania wartości z zakresu?', 1, 'A'),
(30, 3, 'Który operator sprawdza czy wartość znajduje się na liście?', 1, 'B');

INSERT INTO odpowiedzi (id_odp, id_pytania, tresc, literka, poprawna) VALUES
(1, 1, 'SELECT', 'A', 1),
(2, 1, 'GET', 'B', 0),
(3, 1, 'EXTRACT', 'C', 0),
(4, 1, 'FETCH', 'D', 0),

(5, 2, 'HAVING', 'A', 0),
(6, 2, 'WHERE', 'B', 1),
(7, 2, 'FILTER', 'C', 0),
(8, 2, 'LIMIT', 'D', 0),

(9, 3, 'UNIQUE', 'A', 0),
(10, 3, 'DIFFERENT', 'B', 0),
(11, 3, 'DISTINCT', 'C', 1),
(12, 3, 'SINGLE', 'D', 0),

(13, 4, 'INSERT INTO', 'A', 1),
(14, 4, 'ADD ROW', 'B', 0),
(15, 4, 'CREATE INTO', 'C', 0),
(16, 4, 'NEW RECORD', 'D', 0),

(17, 5, 'CHANGE', 'A', 0),
(18, 5, 'UPDATE', 'B', 1),
(19, 5, 'MODIFY', 'C', 0),
(20, 5, 'ALTER', 'D', 0),

(21, 6, 'GROUP BY', 'A', 0),
(22, 6, 'ORDER BY', 'B', 0),
(23, 6, 'SORT BY', 'C', 1),
(24, 6, 'ARRANGE', 'D', 0),

(25, 7, 'COUNT()', 'A', 1),
(26, 7, 'SUM()', 'B', 0),
(27, 7, 'NUMBER()', 'C', 0),
(28, 7, 'TOTAL()', 'D', 0),

(29, 8, 'TOP', 'A', 0),
(30, 8, 'LIMIT', 'B', 1),
(31, 8, 'ROWNUM', 'C', 0),
(32, 8, 'MAX', 'D', 0),

(33, 9, 'MATCH', 'A', 0),
(34, 9, 'EQUALS', 'B', 0),
(35, 9, 'LIKE', 'C', 1),
(36, 9, 'SIMILAR', 'D', 0),

(37, 10, 'DROP TABLE', 'A', 1),
(38, 10, 'DELETE TABLE', 'B', 0),
(39, 10, 'REMOVE TABLE', 'C', 0),
(40, 10, 'CLEAR TABLE', 'D', 0),

(41, 11, 'Database Management System', 'A', 1),
(42, 11, 'Data Backup Management Service', 'B', 0),
(43, 11, 'Digital Binary Main System', 'C', 0),
(44, 11, 'Database Master Source', 'D', 0),

(45, 12, '1NF', 'A', 0),
(46, 12, '2NF', 'B', 1),
(47, 12, '3NF', 'C', 0),
(48, 12, 'BCNF', 'D', 0),

(49, 13, 'Pole przechowujące hasło', 'A', 0),
(50, 13, 'Pole wskazujące na inną tabelę', 'B', 0),
(51, 13, 'Unikalny identyfikator wiersza', 'C', 1),
(52, 13, 'Pole opcjonalne', 'D', 0),

(53, 14, 'Do łączenia tabel i zachowania spójności', 'A', 1),
(54, 14, 'Do szyfrowania danych', 'B', 0),
(55, 14, 'Do tworzenia kopii zapasowych', 'C', 0),
(56, 14, 'Do sortowania', 'D', 0),

(57, 15, 'Pojedyncze zapytanie SELECT', 'A', 0),
(58, 15, 'Ciąg operacji wykonywanych jako całość', 'B', 1),
(59, 15, 'Błąd bazy danych', 'C', 0),
(60, 15, 'Zmiana struktury tabeli', 'D', 0),

(61, 16, 'Atomicity', 'A', 0),
(62, 16, 'Consistency', 'B', 0),
(63, 16, 'Isolation', 'C', 1),
(64, 16, 'Durability', 'D', 0),

(65, 17, 'Relacyjny', 'A', 1),
(66, 17, 'Hierarchiczny', 'B', 0),
(67, 17, 'Sieciowy', 'C', 0),
(68, 17, 'Płaski', 'D', 0),

(69, 18, 'Kopia tabeli', 'A', 0),
(70, 18, 'Struktura przyspieszająca wyszukiwanie', 'B', 1),
(71, 18, 'Rodzaj zapory sieciowej', 'C', 0),
(72, 18, 'Hasło administratora', 'D', 0),

(73, 19, 'SELECT', 'A', 0),
(74, 19, 'INSERT', 'B', 0),
(75, 19, 'CREATE', 'C', 1),
(76, 19, 'UPDATE', 'D', 0),

(77, 20, 'SELECT', 'A', 1),
(78, 20, 'CREATE', 'B', 0),
(79, 20, 'DROP', 'C', 0),
(80, 20, 'ALTER', 'D', 0),

(81, 21, 'INNER JOIN', 'A', 0),
(82, 21, 'FULL OUTER JOIN', 'B', 1),
(83, 21, 'LEFT JOIN', 'C', 0),
(84, 21, 'RIGHT JOIN', 'D', 0),

(85, 22, 'ORDER BY', 'A', 0),
(86, 22, 'SORT BY', 'B', 0),
(87, 22, 'GROUP BY', 'C', 1),
(88, 22, 'CLUSTER BY', 'D', 0),

(89, 23, 'HAVING działa na pogrupowanych danych', 'A', 1),
(90, 23, 'WHERE działa na grupach', 'B', 0),
(91, 23, 'Nie ma różnicy', 'C', 0),
(92, 23, 'HAVING jest starsze', 'D', 0),

(93, 24, 'Super zapytanie', 'A', 0),
(94, 24, 'Podzapytanie (Subquery)', 'B', 1),
(95, 24, 'In-line view', 'C', 0),
(96, 24, 'Query in query', 'D', 0),

(97, 25, 'JOIN', 'A', 0),
(98, 25, 'UNION ALL', 'B', 0),
(99, 25, 'UNION', 'C', 1),
(100, 25, 'MERGE', 'D', 0),

(101, 26, 'MAX()', 'A', 1),
(102, 26, 'TOP()', 'B', 0),
(103, 26, 'HIGHEST()', 'C', 0),
(104, 26, 'UPPER()', 'D', 0),

(105, 27, 'LEFT JOIN', 'A', 0),
(106, 27, 'INNER JOIN', 'B', 1),
(107, 27, 'FULL JOIN', 'C', 0),
(108, 27, 'CROSS JOIN', 'D', 0),

(109, 28, 'Do szyfrowania kolumny', 'A', 0),
(110, 28, 'Do ukrywania tabeli', 'B', 0),
(111, 28, 'Do nadawania tymczasowej nazwy kolumnie lub tabeli', 'C', 1),
(112, 28, 'Do zmiany typu danych', 'D', 0),

(113, 29, 'BETWEEN', 'A', 1),
(114, 29, 'RANGE', 'B', 0),
(115, 29, 'WITHIN', 'C', 0),
(116, 29, 'LIMIT', 'D', 0),

(117, 30, 'CONTAIN', 'A', 0),
(118, 30, 'IN', 'B', 1),
(119, 30, 'LIST', 'C', 0),
(120, 30, 'MEMBER', 'D', 0);