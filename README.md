# Internet Café Backend (Part 6 & 7)

Plain PHP + PDO backend for the Computer Rental Management System.
No framework — just drop this into XAMPP and go.

## 1. Setup

1. Copy the `SME_backend` folder into `C:\xampp\htdocs\` (Windows) or
   `/Applications/XAMPP/htdocs/` (Mac). Rename it to whatever you want your
   project to be called, e.g. `htdocs/internetcafe/`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Make sure the `internetcafe_db` database is imported in phpMyAdmin
   (you already have this — it's the .sql file you shared).
4. Check `config/database.php` — the default XAMPP credentials
   (`root` / no password) are already set. Change them only if your
   MySQL setup is different.
5. Test the connection by visiting:
   `http://localhost/internetcafe/api/computers/read.php`
   You should see your 10 seeded computers as JSON.

## 2. Endpoints so far

| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/auth/login.php` | Log in (username + password) |
| GET | `/api/computers/read.php` | List all computers |
| GET | `/api/computers/read.php?id=3` | Get one computer |
| POST | `/api/computers/create.php` | Add a computer |
| PUT | `/api/computers/update.php?id=3` | Edit a computer |
| DELETE | `/api/computers/delete.php?id=3` | Remove a computer |
| GET | `/api/customers/read.php` | List all customers |
| GET | `/api/customers/read.php?id=2` | Get one customer |
| POST | `/api/customers/create.php` | Add a customer |
| PUT | `/api/customers/update.php?id=2` | Edit a customer |
| DELETE | `/api/customers/delete.php?id=2` | Remove a customer |

## 3. Testing in Postman (Part 8 of the lab)

**Login**
- Method: POST
- URL: `http://localhost/internetcafe/api/auth/login.php`
- Body → raw → JSON:
  ```json
  { "username": "admin", "password": "admin123" }
  ```

**Get all computers**
- Method: GET
- URL: `http://localhost/internetcafe/api/computers/read.php`

**Create a computer**
- Method: POST
- URL: `http://localhost/internetcafe/api/computers/create.php`
- Body → raw → JSON:
  ```json
  {
    "computer_number": "PC-11",
    "computer_name": "Station 11",
    "specifications": "Intel i5, 8GB RAM",
    "hourly_rate": 20.00,
    "status": "Available"
  }
  ```

**Update a computer**
- Method: PUT
- URL: `http://localhost/internetcafe/api/computers/update.php?id=11`
- Body → raw → JSON:
  ```json
  { "status": "Maintenance" }
  ```

**Delete a computer**
- Method: DELETE
- URL: `http://localhost/internetcafe/api/computers/delete.php?id=11`

**Create a customer**
- Method: POST
- URL: `http://localhost/internetcafe/api/customers/create.php`
- Body → raw → JSON:
  ```json
  { "customer_name": "Jose Rizal", "contact_number": "09991234567", "email": "jose@example.com" }
  ```

**Get / Update / Delete a customer** work exactly like the computers ones,
just swap `computers` for `customers` in the URL.

Take screenshots of each of these working — that's your Part 8 deliverable.

## 4. Every response follows the same shape

```json
{ "success": true, "data": {...}, "message": "..." }
{ "success": false, "message": "..." }
```

This makes it predictable to consume from the frontend later (Part 10).

## 5. Not built yet — next steps

- `api/rentals/` — the transaction module. This one's different: starting
  a rental needs to also flip the computer's `status` to `Occupied`, and
  ending a rental needs to calculate `duration_minutes` and
  `total_amount` from `time_in`/`time_out`, then flip the computer back
  to `Available`. Ask when you're ready for this one — it's the most
  important part of the lab (Part 11: one complete business process).
- Passwords are stored in plain text right now to keep things moving;
  switch to `password_hash()` / `password_verify()` before final
  submission (see the note in `login.php`).
