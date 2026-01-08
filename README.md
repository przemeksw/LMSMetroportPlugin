# Plugin do obsługi API Metroport – FSECURE / METROTV

## 1. Instalacja

1) Wtyczkę umieszczamy w katalogu `<lms-path>/plugins/LMSMetroportPlugin`.
2) Aktywujemy wtyczkę z poziomu menu „Konfiguracja” → „Wtyczki” LMS-a.

## 2. Konfiguracja

Dane rejestracyjne platformy Metroport są przechowywane w ustawieniach LMS:
- `metroport.api_login` – login API uzyskany od Metroport,
- `metroport.api_pass` – hasło API uzyskane od Metroport,
- `metroport.serwer_url` – adres URL API do serwerów Metroport,
- `metroport.api_token_expiration_time` – czas życia tokena (nie modyfikować bez potrzeby),
- `metroport.automatically_update_customer_data` – `true` automatycznie aktualizuje dane w MMS przy edycji klienta; `false` wymaga ręcznej edycji.

## 3. Skrypty

- `bin/metroport_customer_update.php` — paruje klientów LMS z MMS na podstawie NIP/PESEL; do uruchomienia jednorazowo lub cyklicznie, aktualizuje powiązania. Po sparowaniu w LMS → METROPORT → Lista Klientów w nawiasie na brązowo pojawi się nazwa klienta z LMS.
- `bin/metroport_fsecure_packages_update.php` — synchronizuje listę pakietów F-SECURE z MMS do LMS (tabela `metroport_fsecure_packages`).
- `bin/metroport_metrotv_packages_update.php` — synchronizuje pakiety MetroTV (podstawowe i dodatkowe) z MMS do LMS (tabela `metroport_metrotv_packages`).
- `bin/metroport_metrotv_stb_list_update.php` — aktualizuje listę dekoderów STB (magazyn) z MMS do LMS (tabela `metroport_metrotv_stb`).

## 4. Blokady z poziomu `lms-notify`

Plugin umożliwia automatyczne wykonywanie blokad usług świadczonych przez Metroport z wykorzystaniem skryptu `lms-notify.php`.

**Przykład użycia:**

- Dla MetroTV

```bash
php ./lms-notify.php -t debtors -s bokada_metroport -c block   -a metrotv-locks,customer-status
php ./lms-notify.php -t debtors -s bokada_metroport -c unblock -a metrotv-locks,customer-status
```

- Dla FSECURE

```bash
php ./lms-notify.php -t debtors -s bokada_metroport -c unblock -a metrofsecure-locks,customer-status
php ./lms-notify.php -t debtors -s bokada_metroport -c block   -a metrofsecure-locks,customer-status
```

- Dla MetroTV i FSECURE

```bash
php ./lms-notify.php -t debtors -s bokada_metroport -c unblock -a metrofsecure-locks,metrotv-locks,customer-status
php ./lms-notify.php -t debtors -s bokada_metroport -c block   -a metrofsecure-locks,metrotv-locks,customer-status
```

## 5. Dane przekazywane do Metroport

Minimalny zestaw danych wymagany do aktywacji produktów:
- imię i nazwisko / nazwa,
- adres,
- NIP / PESEL,
- adres e-mail.

## 6. Możliwości pluginu

- [x] 6.1 Globalne
    - [x] 6.1.1 Dodawanie klienta
    - [x] 6.1.2 Modyfikowanie klienta
    - [x] 6.1.3 Automatyczna modyfikacja danych klienta w MMS
    - [x] 6.1.4 Lista klientów MMS wraz z wyświetlaniem powiązania do LMS
    - [x] 6.1.5 Skrypt backendowy parujący klientów MMS z LMS na podstawie NIP i PESEL (wymagane wartości)
    - [x] 6.1.6 Test połączenia
    - [x] 6.1.7 Obsługa lms-notify – automatyczne blokady (np. brak płatności)

- [x] 6.2 FSECURE
    - [x] 6.2.1 Dodawanie usługi
    - [x] 6.2.2 Kasowanie usługi
    - [x] 6.2.3 Modyfikowanie usługi
    - [x] 6.2.4 Lista dostępnych pakietów do wydania
    - [x] 6.2.5 Lista klientów z wydaną usługą F-SECURE
    - [x] 6.2.6 Lista operacji F-SECURE
    - [x] 6.2.7 Po zaznaczeniu checkbox dodanie zobowiązania do karty zobowiązania (wymaga taryfy w Finanse → Nowa taryfa)

- [x] 6.3 METROTV
    - [x] 6.3.1 Dodawanie konta
    - [x] 6.3.2 Modyfikowanie konta (dane adresowe; zmiana sieci nie działa)
    - [x] 6.3.3 Kasowanie konta
    - [x] 6.3.4 Wydawanie pakietu podstawowego (lista filtruje `pkg_valid_from` oraz `active`)
    - [x] 6.3.5 Kasowanie wydawanego pakietu podstawowego
    - [x] 6.3.6 Wydawanie pakietu dodatkowego (filtr `pkg_valid_from`, `active`, `pkg_denied` z pakietu podstawowego)
    - [x] 6.3.7 Kasowanie pakietu dodatkowego
    - [x] 6.3.8 Wydawanie STB; lista dekoderów z wyszukiwaniem, tylko magazyn
    - [x] 6.3.9 Kasowanie STB
    - [x] 6.3.10 Kasowanie elementów od dołu: STB → pakiety dodatkowe → pakiet podstawowy → konto (brak przycisku kasuj, gdy istnieje kolejny element)
    - [x] 6.3.11 Lista pakietów
    - [x] 6.3.12 Lista podsieci z liczbą wydanych STB oraz możliwych do wydania
    - [x] 6.3.13 Lista modeli STB
    - [x] 6.3.14 Lista STB + dodawanie STB na magazyn
    - [x] 6.3.15 Lista kont Metro TV z wartościami przypisanych pakietów

## 7. TODO (niewykonane)

- [ ] 7.1 Import bilingów MetroTV i F-SECURE z systemu Metroport (czekamy na wsparcie Metroport)
- [ ] 7.2 Dodawanie pozycji bilingowych do wystawianych faktur lub tworzenie nowych faktur na podstawie bilingów (czekamy na wsparcie Metroport)


    

