MR SZPONT

## Klasy nauczycieli

W panelu nauczyciela otwórz **Moje klasy**, utwórz klasę i dodaj uczniów
przez login istniejącego konta. Konto nauczyciela nie może zostać dodane jako uczeń.
Każdy nauczyciel zarządza wyłącznie swoimi klasami.

Wyniki uczniów pokazują ostatnie 50 podejść osób należących do klas zalogowanego
nauczyciela. Można wybrać jedną klasę lub wszystkie swoje klasy. Uczeń należący
do kilku klas nie powoduje powielenia wyników. Nauczyciel bez klas lub uczniów
nie widzi wyników innych kont. Dodanie ucznia udostępnia także jego wcześniejsze
wyniki. Usunięcie ucznia z ostatniej klasy danego nauczyciela odbiera temu
nauczycielowi dostęp do jego wyników, ale nie usuwa konta ani punktów ucznia.

### Istniejąca baza danych

Uruchom `classes.sql` w istniejącej bazie `Testy`, np. w phpMyAdmin lub:

```powershell
& C:\xampp\mysql\bin\mysql.exe -u root --database=Testy --execute="source C:/xampp/htdocs/Projekt-master/classes.sql"
```

Migracja dodaje dwie tabele i nie usuwa danych. Można ją uruchomić ponownie.
Nie przypisuje istniejących uczniów automatycznie do nauczycieli.

Przy nowej instalacji `baza.sql` zawiera już tabele klas. Import tego pliku
tworzy bazę od nowa, więc do aktualizacji używaj `classes.sql`.

Testy pozostają wspólne dla platformy. Klasy ograniczają widoczność wyników;
uczeń nadal widzi własną historię niezależnie od przynależności do klas.

### Sprawdzenie uprawnień klas

Przy uruchomionym Apache i MySQL w XAMPP:

```powershell
& C:\xampp\php\php.exe C:\xampp\htdocs\Projekt-master\tests\classrooms.php
```

Test tworzy tymczasowe konta, klasy i wyniki, sprawdza dostęp dwóch nauczycieli
oraz ucznia, a następnie usuwa swoje dane testowe.
