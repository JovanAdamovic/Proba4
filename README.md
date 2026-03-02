# Veb Aplikacija za Evidenciju Radova
Ova aplikacija je kreirana za potrebe predmeta Internet Tehnologije na Fakultetu organizacionih nauka

## Opis projekta
Sistem omogućava evidenciju predmeta, zadataka i studentskih predaja radova, sa podelom funkcionalnosti po ulogama korisnika:
- **ADMIN** upravlja podacima sistema,
- **PROFESOR** kreira i prati zadatke i predaje na svojim predmetima,
- **STUDENT** pregleda zadatke i predaje svoje radove.

Aplikacija je podeljena na:
- **Laravel backend** (REST API + autentifikacija),
- **React frontend** (korisnički interfejs),
- **MySQL bazu**, sve pokrenuto preko Docker okruženja.

## Šta je potrebno instalirati 
Da bi aplikacija mogla da se pokrene, potrebno je instalirati sledeće alate:
- Docker Desktop – Docker omogućava pokretanje baze, backend-a i frontend-a u izolovanom okruženju bez ručne instalacije PHP-a, MySQL-a i Node-a.
- Git – Koristi se za kloniranje repozitorijuma i verzionisanje koda.

## Kako preuzeti projekat
1. Kloniranje repozitorijuma
U terminalu pokrenuti:
```bash
git clone https://github.com/elab-development/internet-tehnologije-2025-vebappzaevidencijuradova_2022_0336.git
cd internet-tehnologije-2025-vebappzaevidencijuradova_2022_0336
```

2. Pokretanje aplikacije preko Docker-a
U root folderu projekta (gde se nalazi docker-compose.yml) u terminalu pokrenuti:
```bash
docker compose up -d --build
```
Ova komanda podiže sve servise (frontend, backend, bazu).

3. Podešavanje Laravel aplikacije
U folderu evidencije_laravel podesiti .env fajl (ako ne postoji, kopirati iz .env.example) i postaviti sledeća podešavanja za DB_*

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=app_db
DB_USERNAME=app_user
DB_PASSWORD=app_pass
```
Zatim pokrenuti:
```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan config:clear
docker compose exec app php artisan migrate

docker compose exec app php artisan db:seed
```

4. Pristup aplikaciji
Nakon uspešnog pokretanja aplikacije će biti dostupne na sledećim linkovima:
Frontend: http://localhost:3000
Backend: http://localhost:8000
Alternativni backend port: http://localhost:8080

## Opis funkcionalnosti projekta
- Prijava korisnika i rad sa token autentifikacijom.
- Upravljanje predmetima (pregled, dodavanje, izmena, brisanje).
- Upravljanje zadacima po predmetima.
- Predaja studentskih radova.
- Pregled i ocenjivanje predaja.
- Pokretanje provere plagijata za predaju.
- Kalendar rokova za studente i profesore
