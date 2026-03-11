# L66-PIM

Zadanie rekrutacyjne L66 sp. z o. o. - Moduł PIM

## Tech stack

- PHP 8.5
- Symfony 7.4
- Doctrine ORM + PostgreSQL
- Lexik JWT + Gesdinet refresh tokens
- Docker Compose
- PHPUnit, PHPStan, PHP-CS-Fixer

## Uruchomienie

```bash
make start
```

## Statyczna analiza kodu i testy

```bash
make prepare-pr
```

# Decyzje architektoniczne

## 1. Architektura: modularny monolit
Projekt został podzielony na moduły `Product` i `Security`.
Decyzja wynikała z zakresu zadania rekrutacyjnego i dość krótkiego czasu wykonania zadania.

Najważniejsze konsekwencje:
- Czytelny podział odpowiedzialności w kodzie.
- Prostszy deployment i lokalne uruchomienie przez Docker Compose.
- Łatwiejsze utrzymanie spójności transakcyjnej.

## 2. Podział na ścieżkę zapisu i odczytu
W warstwie aplikacyjnej przyjęto model CQRS:
- komendy (odpowiedzialne za wykonanie logiki biznesowej związanej z zapisem).
- query (odpowiedzialne za wykonanie odczytów danych).

## 3. Model domenowy produktu i niezmienniki
W module `Product` logika domenowa została zamknięta w modelu i Value Objectach (takich jak `Price`).

Najważniejsze reguły:
- soft delete przez `deleted_at` (rekord nie jest fizycznie usuwany).
- SKU unikalne dla aktywnych i nieusuniętych produktów.
- historia zmian ceny zapisywana jako zdarzenia biznesowe.

## 4. Historia cen przez zdarzenie domenowe
Zmiana ceny emituje zdarzenie `ProductPriceChanged`, które jest obsługiwane synchronicznie przez handler.
Efekt zapisywany jest w tabeli `product_events` wraz z osobą wywołującą zmianę, czasem i payloadem (poprzednia/nowa cena).

## 5. Współbieżność: optimistic locking + kontrakt HTTP
Dla encji `Product` dodano wersjonowanie (pole `version`) i optimistic lock Doctrine.
Na poziomie API przyjęto kontrakt:
- `GET /api/products/{id}` zwraca nagłówek `ETag` z numerem wersji.
- `PUT` i `DELETE` wymagają `If-Match`.
- brak `If-Match` zwraca kod `428`.
- nieaktualna wersja zwraca kod `409`.

To ogranicza ryzyko nadpisania zmian przy równoczesnej edycji przez dwóch użytkowników.

## 6. Persistencja i spójność danych
Zastosowano PostgreSQL + Doctrine ORM oraz migracje jako źródło prawdy dla schematu.

## 7. Bezpieczeństwo: JWT + refresh token
Autoryzacja oparta jest o:
- `lexik/jwt-authentication-bundle` dla access tokenów.
- `gesdinet/jwt-refresh-token-bundle` dla odświeżania i wylogowania.

Pozwala to zachować prosty, statelessowy dostęp do API przy zachowaniu kontrolowanego cyklu życia sesji.

## 8. Strategia testów
W projekcie pozostawiono testy integracyjne skupione na:
- command handlerach
- query

Świadoma decyzja:
- rezygnacja z testów endpointów HTTP na rzecz testów logiki aplikacyjnej i warstwy persistence.
- uproszczenie utrzymania testów przy jednoczesnym pokryciu krytycznych ścieżek biznesowych.

## 9. Świadomie odłożone usprawnienia (backlog)
- Rozbudowane zarządzanie rolami i autoryzacją (więcej ról i polityki dostępu) - w zależności od potrzeb systemu.
- Wydzielenie factory/buildera do składania danych produktu z historią zmian.
- Dodatkowe testy integracyjne na poziomie endpointów HTTP i jednostkowe na poziomie VO.
- Zcentralizowanie mapowania wyjątków HTTP (ErrorController/ExceptionListener).
- Dodanie readmodelu na produkt (zapis do tabeli z domenowymi obiektami oraz aktualizacja readmodelu służącego jedynie do odczytu).
- Obsługa tranzakcyjności dla aktualizacji produktu i historii ceny (Outbox pattern).
- Wydzielenie dedykowanych bus-ów na command/event/query w messengerze, w tym dodanie asynchroniczności (transport Doctrine lub RabbitMQ).
- Nazewnictwo commit-ów zgodnie z [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/).
- Rozdzielenie zmian na mniejsze części i wystawianie pull requestów z każdą z nich.

## 10. Dokumentacja API
Dostępna pod ścieżką `api/doc` / `api/doc.json`
