-- Add classes to an existing Testy database without changing accounts or scores.
CREATE TABLE IF NOT EXISTS klasy (
    id int unsigned AUTO_INCREMENT PRIMARY KEY,
    id_nauczyciela int unsigned NOT NULL,
    nazwa varchar(100) NOT NULL,
    UNIQUE KEY uq_klasa_nauczyciel (id_nauczyciela, nazwa),
    CONSTRAINT fk_klasy_nauczyciel FOREIGN KEY (id_nauczyciela)
        REFERENCES uzytkownicy(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uczniowie_klas (
    id_klasy int unsigned NOT NULL,
    id_ucznia int unsigned NOT NULL,
    PRIMARY KEY (id_klasy, id_ucznia),
    KEY idx_uczniowie_uczen (id_ucznia),
    CONSTRAINT fk_uczniowie_klasa FOREIGN KEY (id_klasy)
        REFERENCES klasy(id) ON DELETE CASCADE,
    CONSTRAINT fk_uczniowie_konto FOREIGN KEY (id_ucznia)
        REFERENCES uzytkownicy(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
