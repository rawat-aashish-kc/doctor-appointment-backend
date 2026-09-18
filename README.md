# Doctor Appointment Booking — Backend

Laravel API for a clinic appointment booking system with three portals: Admin, Patient, and Doctor. Sanctum token auth, MySQL, Pest tests.

## Requirements

- PHP 8.3+
- Composer
- MySQL (or any Laravel-supported DB — update `.env` accordingly)
- Node.js (only needed if you also run `npm run dev`/`composer run dev` for asset watching; not required to serve the API)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` and point it at your database:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=doctor_appointment_backend
DB_USERNAME=root
DB_PASSWORD=
```

Create the database (e.g. `mysql -u root -e "CREATE DATABASE doctor_appointment_backend"`), then migrate and seed:

```bash
php artisan migrate:fresh --seed
```

Seeding creates one admin account, a handful of sample doctors (each with their own login), and their weekly availability. See **Seeded accounts** below.

Start the API:

```bash
php artisan serve
```

The API is served at `http://localhost:8000`, all routes prefixed `/api/v1` (e.g. `http://localhost:8000/api/v1/login`). The frontend expects this exact base URL by default — see the frontend README.

## Seeded accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@clinic.test` | `password` |
| Doctor (Dr. Alice Nguyen) | `alice.nguyen@clinic.test` | `password` |
| Doctor (Dr. Ben Carter) | `ben.carter@clinic.test` | `password` |
| Doctor (Dr. Priya Shah) | `priya.shah@clinic.test` | `password` |

Patients aren't seeded — register one from the frontend's `/register` page.

## Running tests

```bash
php artisan test --compact
```

## Auth model

Sanctum in **token mode**: `POST /api/v1/login` (any role) or `POST /api/v1/register` (patients only) returns a bearer token. Send it as `Authorization: Bearer <token>` on subsequent requests. There's no session/cookie auth — this is a plain token API, safe to call from a frontend on a different port/origin.

## Domain notes

- A doctor is a profile record (`doctors` table), optionally linked to a login-capable `users` row (`role: doctor`) — created together when admin adds a doctor with an email+password.
- A doctor can have **multiple availability periods per day** (e.g. 9–1 and 2–5) — the gap between periods is never offered as a bookable slot.
- Admin can add a one-off **break** for a doctor on a specific date. If it overlaps an existing booked appointment, that appointment is automatically moved to the nearest free slot that same day, or cancelled if nothing is free.
- Appointment slots are always computed server-side (`App\Services\DoctorSlotFinder`) from availability minus breaks minus existing bookings — the frontend never decides what's bookable.
