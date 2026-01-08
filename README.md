Plugin do obsługi API Metroport - FSECURE METROTV

1. INSTALACJA

    1) Wtyczkę umieszczamy w katalogu <lms-path>/plugins/LMSMetroportPlugin
    2) Aktywujemy wtyczkę z poziomu menu "Konfiguracja"/"Wtyczki" LMS-a.

2. KONFIGURACJA

    Dane rejestracyjne platformy Metroport są przechowywane w ustawieniach LMS:
    metroport.api_login - login api uzyskany od Metrport,
    metroport.api_pass - hasło api uzyskane od Metroport,
    metroport.serwer_url - serwer url do połączenia api z serwerami Metroport,
    metroport.api_token_expiration_time - czas życia tokena, nie modyfikować bez potrzeby,
    metroport.automatically_update_customer_data -  true - przy edycji klienta automatycznie aktualizuje dane w MMS, false wymaga ręcznej edycji

3. SKRYPTY

    - bin/metroport_customer_update.php — paruje klientów LMS z klientami w MMS na podstawie NIP/PESEL; umożliwia jednorazowe lub cykliczne uruchomienie i aktualizuje  powiązania. (   Skrypt można uruchomić raz w momencie uruchomienia wtyczki, lub cyklicznie, jeśli klienci są dodawani równolegle w systemie MMS.
			      Przy każdym sparowanym kliencie w LMS->METROPORT->Lista KLientów w nawiasie na brązowo pojawi się nazwa klienta z LMS )
    - bin/metroport_fsecure_packages_update.php — synchronizuje listę pakietów F‑Secure z MMS do LMS, aby były dostępne do wydawania z poziomu własnych implementacji kodu. Dane zapisywane są do tabeli metroport_fsecure_packages.
    - bin/metroport_metrotv_packages_update.php — synchronizuje listę pakietów MetroTV (podstawowe i dodatkowe) z MMS do LMS, aby były dostępne do wydawania z poziomu własnych implementacji kodu. Dane zapisywane są do tabeli metroport_metrotv_packages.
    - bin/metroport_metrotv_stb_list_update.php — aktualizuje listę dekoderów STB (magazyn) z MMS do LMS na potrzeby wydawania aby były dostępne do wydawania z poziomu własnych implementacji kodu. Dane zapisywane sa do tabeli metroport_metrotv_stb.


4. BLOKADY Z POZIOMU LMS NOTIFY

    Plugin umożliwia automatyczne wykonywanie blokady usług świadczonych przez Metroport z wykorzystaniem skryptu lms-notify.php			   
        Przykład użycia:
        Dla MetroTV
            php ./lms-notify.php -t debtors -s bokada_metroport  -c block -a metrotv-locks,customer-status 
            php ./lms-notify.php -t debtors -s bokada_metroport  -c unblock -a metrotv-locks,customer-status
        Dla Fsecure
            php ./lms-notify.php -t debtors -s bokada_metroport  -c unblock -a metrofsecure-locks,customer-status
            php ./lms-notify.php -t debtors -s bokada_metroport  -c block -a metrofsecure-locks,customer-status
        Dla MetroTV i Fsecure
            php ./lms-notify.php -t debtors -s bokada_metroport  -c unblock -a metrofsecure-locks,metrotv-locks,customer-status
            php ./lms-notify.php -t debtors -s bokada_metroport  -c block -a metrofsecure-locks,metrotv-locks,customer-status



5. DANE PRZEKAZYWANE DO METROPORT

    Wtyczka przekazuje minimum informacji które są wymagane do aktywacji poszczególnych produktów:
    - imię nazwisko/nazwa
    - adres
    - nip/pesel
    - adres email


6. MOŻLIWOŚCI PLUGINU

    6.1. Globalne
        6.1.1 Dodawanie klienta,
        6.1.2 Modyfikowanie klienta,
        6.1.3 Automatyczna modyfikacja danych klienta w MMS,
        6.1.4 Lista klientów MMS wraz z wyświetlaniem powiązania do LMS,
        6.1.5 Skrypt backendowy który globalnie paruje klientów MMS z LMS na podstawie NIP i PESEL (wymagane wartości),
        6.1.6 Test połączenia,
        6.1.7 Obsluga lms-notify - wykonywanie automatycznych blokad z powodu np braku płatności
    6.2 Fsecure
        6.2.1 Dodawanie usługi,
        6.2.2 Kasowanie usługi,
        6.2.3 Modyfikowanie usługi,
        6.2.4 Lista dostępnych pakietów do wydania,
        6.2.5 Lista klientów z  wydaną usługa F-SECURE,
        6.2.6 Lista operacji F-SECURE,
        6.2.7 Po zaznaczeniu checkbox dodanie zobowiązania do karty zobowiązania ( wcześniej musi zostać w LMS skonfigurowany dana taryfa w zakładce Finanse->NowaTaryfa
    6.3 METROTV
        6.3.1 Dodawanie konta,
        6.3.2 Modyfikowanie konta ( dane adresowe ) - zmiana sieci nie działa,
        6.3.3 Kasowanie konta,
        6.3.4 Wydawanie pakietu podstawowego - lista filtrowana jest na podstawie pozycji pkg_valid_from, oraz active ,
        6.3.5 Kasowanie wydawanego pakietu podstawowego,
        6.3.6 Wydawanie pakietu dodatkowego - Pakiety dodatkowe do wydania filtrowane są na podstawie pkg_valid_from, active oraz pkg_denied z pakietu podstawowego,
        6.3.7 Kasowanie pakietu dodatkowego,
        6.3.8 Wydawanie STB, lista dekoderów z możliwością wyszukiwania, jest wyświetlana tylko dla dekoderów które są na magazynie,
        6.3.9 Kasowanie STB,
        6.3.10 Kasowanie każdego z elementów musi się odbywać zawsze od dołu, czyli kasujemy STB, pakiety dodatkowe, pakiet podstawowy i dopiero konto. System nie wyświetla guzika kasuj w momencie kiedy jest dodany kolejny element,
        6.3.11 Lista pakietów ,
        6.3.12 Lista podsieci wraz z ilością wydanych STB, a także ilością STB które można jeszcze wydać,
        6.3.13 Lista modeli STB
        6.3.14 Lista STB + dodawanie STB na magazyn
        6.3.15 Lista kont Metro TV wraz z wartościami pakietów jakei sa do nich przypisane


7. TODO
    7.1. Import bilingów MetroTV i F-SECURE z systemu metroport - czekamy na wsparcie Metroport,
    7.2. Dodawanie pozycji bilingowych do wystawianych faktur lub tworzenie nowych faktur na podstawie bilingów - czekamy na wsparcie Metroport. 


    

