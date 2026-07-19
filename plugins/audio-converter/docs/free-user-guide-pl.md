# Szybki przewodnik - Wersja darmowa

Ten przewodnik jest samowystarczalny i nie wymaga zewnetrznej strony.

## Wymagania

- WordPress 7.0+
- PHP 8.0+
- Co najmniej jeden skonfigurowany i aktywny konektor AI WordPress
- Uzytkownik z uprawnieniami do edycji wpisow

Wazne: bez aktywnego konektora AI wtyczka moze byc aktywna, ale nie wygeneruje szkicow z audio.

## 1) Instalacja

1. Skopiuj folder wtyczki do wp-content/plugins.
2. Aktywuj Audio Converter.
3. Przejdz do Ustawienia > Audio Converter.
4. Zapisz ustawienia domyslne.

## 2) Zalecana konfiguracja

- Domyslny jezyk: glowny jezyk strony
- Domyslny ton: professional
- Domyslna dlugosc: medium
- Podpowiedzi nazw wlasnych: marki, osoby, miejsca, produkty
- Tryb wstawiania: append (zalecany)

## 3) Uzycie w edytorze blokowym

1. Otworz lub utworz wpis.
2. Otworz panel boczny Audio Converter.
3. Kliknij Select audio from Media Library.
4. Wybierz plik audio.
5. Sprawdz opcje redakcyjne.
6. Kliknij Generate draft from audio.

## 4) Oczekiwany rezultat

- Wtyczka wstawia bloki Gutenberg do aktualnego wpisu.
- Jesli wlaczone, wygenerowany tytul jest stosowany do wpisu.
- W przypadku bledu pojawia sie komunikat w panelu bocznym.

## 5) Szybkie rozwiazywanie problemow

- Brak panelu bocznego: sprawdz czy wtyczka jest aktywna i czy uzywasz edytora blokowego.
- Blad dostawcy AI: sprawdz konfiguracje konektorow AI.
- Brak wstawionych blokow: sprawdz komunikat i sproboj z czystszym audio.

## 6) Dobre praktyki

- Uzywaj wyraznego audio z malym szumem tla.
- Dodawaj podpowiedzi nazw wlasnych.
- Dla lepszych wynikow uzywaj krotszych i wyraznych nagran.
